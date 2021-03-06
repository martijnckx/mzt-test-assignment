
# MyZenTeam code test assignment

This is my continuation on [MyZenTeam code test assignment](https://github.com/siljanoskam/mzt-test-assignment)

## Setup

### Prerequisites

- Have PHP installed
- Have Composer installed
- Have MySQL installed

### Running the app

1. Get the source code on your local machine by pulling this repo
2. Navigate to the root folder
3. Run `composer install`
4. Run `cp .env.example .env`
5. Run `php artisan key:generate`
6. Run `npm ci && npm run dev`
7. Create a MySQL database
8. Create a MySQL user and grant permission to this new database (but nothing extra 😉). I used this command, generated in [TablePlus](https://tableplus.com/). Don't forget to replace {username}, {password}, and {database_name} without the brackets if you use this command, as well as modify its location if you don't run it locally:

    ```SQL
    CREATE USER '{username}'@'localhost' IDENTIFIED BY '{password}';

    GRANT CREATE ROUTINE, CREATE TEMPORARY TABLES, DROP, ALTER ROUTINE, TRIGGER, SHOW VIEW, CREATE, DELETE, EVENT, ALTER, EXECUTE, INSERT, SELECT, CREATE VIEW, INDEX, REFERENCES, GRANT OPTION, UPDATE, LOCK TABLES ON `{database_name}`.* TO '{username}'@'localhost';

    FLUSH PRIVILEGES;
    ```

9. Add the MySQL info into the `.env` file at `DB_*`
10. Configure the rest of the `.env` file (ie `APP_*` and `MAIL_*` vars)
11. Optionally, add `SKIP_MAILS=true` to the `.env` file to not actually send contact and hire emails
11. Run `php artisan migrate`
12. Run `php artisan db:seed`
13. Run `php artisan serve`

## Process

(I wrote this live as I was working, apologies for mixing past and present tense 😅)

### Game plan

After getting the app up and running and going to the candidates list, the first thing I noticed was that the wallet said it had "? coins". Feeling like knowing why this is the case would be a good first point to get to know the codebase. As a first simple check, I opened up the wallets table in the database to confirm it should logically show a value, and it should. The table got intialised with 20 in the wallet connected to company #1. Let's dig in.

### Wallet relationship

I opened up the routes file (`/routes/web.php`) and followed the URL path to its controller and function `CandidateController` > `index`. There, it became apparent (with the help of some debug log statements) the company was fetched correctly, but only the properties from the company table were present. The relationship was not (correctly?) defined. Time to take a look. Opening `/app/Models/Company.php` reveals there is indeed no relationship defined. Let's get that fixed:

```php
public function coins()
    {
        return $this->hasOne(Wallet::class);
    }
```

I considered adding a default model with the snippet below, but decided against it in favour of actual checks and throwing errors, because if there isn't a wallet, that's quite a big problem.

```php
->withDefault([
        'coins' => 0,
    ]);
```

Since really a company's only function in the app is to have a name and a wallet, I decided it's a good optimisation to eagerly load a company's wallet by default:

```php
class Company extends Model
{
    ...
    protected $with = ['wallet'];
    ...
}
```

For good measure, I also defined the inverse relationship:

```php
class Wallet extends Model
{
    ...
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    ...
}
```

To get the correct amount displayed, I just had to modify how to controller fetched the coins, using the wallet relationship: `$coins = Company::find(1)->wallet->coins; ` (`->wallet` is the only new thing here).

Now that I am a little familiar with the codebase, I want to go for the first feature.

### First feature: the 'contact' button

After re-reading the assignment, the behaviour of the button in different situations seemed not fully defined.

#### Assumptions

I'll make a few assumptions:

- A company can contact a candidate more than once, to account for undelivered / missed emails.
- Contacting the same person more than once does not result in extra costs
- The UI should indicate if you already contacted a candidate
- Ideally, there's some kind of spam protection in place (eg. limit on contacts per day / based on email activity) → not developing this as part of the assignment

#### Clicking the button

UX wise, I think it makes most sense to implement the contact button asynchronously. Sticking to the POST request would still be possible with a synchronous form submit, but allows for a little less control on the UX front, I feel like. I will start there, so let's take a look at the front-end of this page.

The obvious choice seems to be to add a `contactCandidate()` method to the `Candidates.vue` component and trigger it with a simple `@click` event. There, the POST request could be sent and the user could be informed of the result when the network request resolves. That's assuming identification of the company would be through a cookie that was also sent over. However, it would be more user friendly to do a quick balance check client-side before even sending the network request, as it's not needed if the wallet balance is too low. That way, it saves the user some time (since the feedback comes faster) and bandwidth (since no network request needs to be sent).

The Candidates component has no knowledge of the current company's balance though, so we'll need to emit the event to be handled by its parent component. To accomplish that, I used ` @click="$emit('contact-candidate', candidate)"` on the button and `@contact-candidate="contactCandidate"` on the candidates HTML element to listen to the emitted event.

The callback function is defined in the main template (blade file) and added to the Vue component in `app.js`:

```js
methods: {
    contactCandidate: contactCandidate,
},
```

The implementation of that function should check the balance, send the request to contact the candidate (if balance allows), and show feedback to the user (error with reason or success message). Once the balance check was implemented and the client was ready to send the request, I turned to the implementation of that route.

#### Handling the request

The route is already defined for me, which is nice. I don't think it's necessary to change the HTTP verb, as it complies with REST standards. The implementation should do a few things:

- Check balance
- Send mail to candidate
- Mark candidate as contacted
- Deduct balance

Using a new migration (`php artisan make:migration`), I created a new database table to serve as a pivot table for the many-to-many relationship between companies and candidates ('every candidate can be contacted by multiple companies'). When that was set up and the relationships were defined both ways in the models, I started to implement aforementioned requirements.

The implementation of the contact function start with checking the balance to have at least COST_OF_CONTACT amount of money. That's a constant I created earlier already to pass to the front end via blade (so the client-side balance check is updated in one go when the price to contact a candidate changes).

When that's all good, the function checks the validity of the user input in the request. I opted to only send the candidate ID in a JSON object along with the CSRF token, but that still could contain malicious data. I check for a valid stringified object that contains the 'candidate' property which should be a valid integer (since candidate IDs are integers).

I should point out that would any of these checks fail, I respond with an appropriate HTTP status code and short but descriptive human readable error message, which I intend on showing to the user in the browser.

Afterwards, the email to the candidate is sent out. Sending emails can take a while and waiting for them to get sent may take some time, but allows for error checking should anything go wrong. That way, we can let the user know. Doing it asynchronously after sending the HTTP response gets an answer to the user quicker, but doesn't allow for that check. During testing I used SMTP from Mailgun.

To create the Mailable, I used `php artisan make:mail CandidateContacted`. For the template, I created a new folder at `/resources/views/emails`. I would set the Reply-To field to the email address of the company, but they don't have one in this app. I didn't add it for now.

Lastly, the function checks if the company has contacted this candidate before. If not, the cost of contacting is deducted from their wallet and the fact that this company contacted the candidate is stored.

#### Presenting feedback to the user

Now it's time to process to reply of the server in the browser, and show the error message to the user. For that, I decided to create a `Notifications.vue` component which handles showing and hiding notification banners. It exposes a `showNotification(message, style = 'neutral')` method and allows for `neutral`, `positive`, and `negative` styles (and defaults to `neutral` for everything else).

In the parent, I added a `ref="notifications"` attribute to the notifications element, and called its method with `this.$refs.notifications.showNotification()`.

If for some reason contacting the candidate failed, the user gets a red notification with the error message in it. If all went well, the notifcation is green with a success message in it, and the value of the wallet is set to the updated wallet value (which may be the same as before if a candidate was already contacted by this company in the past).

I also want to indicate to the user that the application is working on it as soon as the user clicks the button, so I added another notification to signal that. Another option would be to add a spinner or change the button text to something like 'Contacting' or 'Please wait...', or disable the button temporarily.

### Second feature: the 'hire' button

The hire button was implemented similarly to the contact button, so I'll limit this description to some key differences.

Firstly, the relationship here is a OneToMany, since a candidate can only be hired by a single company, and a company can hire multiple candidates.

As defined in the assignment, the implementation makes sure a candidate was contacted before by a company.

The function which handles hire requests also checks if a particular candidate was not hired yet, as it wouldn't be allowed in both scenarios (hired by yourself [double refund] or hired by another company).

When a candidate is hired by a company, it is removed from the candidates list. Initially, on hire, by the browser. But it's also taken into account when loading the page, as the only candidates that get sent to the browser are the ones which do not have a `hiredBy` relationship: `$candidates = Candidate::doesntHave('hiredBy')->get()`.

### Testing

As I have indicated in my job application, I don't have active experience with testing yet. I know about the general types of testing (like unit, feature / integration, end-to-end), know I can create them with `php artisan make:test UserTest` and run them with `php artisan test`. That said, I haven't actually used any of those extensively.

A feature that feels like it could use some test is wallets. Contacting a candidate, contacting the same candidate again, hiring them and checking if the balance stays correct along the way. I didn't implement those.

After reading some documentation, I believe I would start a test a little something like this:

```php
$company = Company::factory()
                    ->has(Wallet::factory())
                    ->make();

$candidate = Candidate::factory()
                        ->count(2)
                        ->make();
```

But I'm unsure, yet keen to learn (but rather short on time right now) how to use those with the `CandidateController`'s functions. Because they hard code `Company::find(1)` and authentication is not in scope for this assignment. Also, I don't believe I'm supposed to clone its implementation (that would kinda miss the point, wouldn't it?).

I did quite a bit of manual testing instead, using the browser and Burp Suite, to intercept my requests and modify them with different / malformatted input.

## Quality of life improvements

I made some other changes, listed below

- Fixed the fact that the app was leaking all email addresses through the blade template (printing the entire candidates object).
- Added foreign key constraints to newly created columns in the database.
- Added alt tags to the images, for improved accessibility (and the images are cute cats now).
- Changed top bar with coins balance to a fixed position to still see it when scrolling.
- Made the top element a Tailwind container to set a max width for easier viewing on very wide screens.
- Pulled out some parts of the page into their own Vue components.