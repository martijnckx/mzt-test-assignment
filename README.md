
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
4. Run `php artisan key:generate`
5. Run `npm ci && npm run dev`
6. Create a MySQL database
7. Create a MySQL user and grant permission to this new database (but nothing extra 😉). I used this command, generated in [TablePlus](https://tableplus.com/):

    ```SQL
    CREATE USER 'myzenteam_user'@'localhost' IDENTIFIED BY 'xiqhuqwJ9_jp';

    GRANT CREATE ROUTINE, CREATE TEMPORARY TABLES, DROP, ALTER ROUTINE, TRIGGER, SHOW VIEW, CREATE, DELETE, EVENT, ALTER, EXECUTE, INSERT, SELECT, CREATE VIEW, INDEX, REFERENCES, GRANT OPTION, UPDATE, LOCK TABLES ON `myzenteam`.* TO 'myzenteam_user'@'localhost';

    FLUSH PRIVILEGES;
    ```

8. Run `php artisan migrate`
9. Run `php artisan db:seed`
10. Run `php artisan serve`

## Process

(I wrote this livee as I was working, apologies for mixing past and present tense 😅)

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
    protected $with = ['author'];
    ...
}
```

Foor good measure, I also defined the inverse relationship:

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
- The UI should indicate when you last contacted a candidate
- Ideally, there's some kind of spam protection in place (eg. limit on contacts per day / based on email activity) → not developing this as part of the assignment

### Client-side

UX wise, I think it makes most sense to implement the contact button asynchronously. Sticking to the POST request would still be possible with a synchronous form submit, but allows for a little less control on the UX front, I feel like. I will start there, so let's take a look at the front-end of this page.

The obvious choice seems to be to add a `contactCandidate()` method to the `Candidates.vue` component and trigger it with a simple `@click` event. There, the POST request could be sent and the user could be informed of the result when the network request resolves. However, it would be more user friendly to do a quick balance check client-side before even sending the network request, as it's not needed if the wallet balance is too low. That way, it saves the user some time (since the feedback comes faster) and bandwidth (since no network request needs to be sent).

The Candidates component has no knowledge of the current company's balance though, so we'll need to emit the event to be handled by its parent component. To accomplish that, I used ` @click="$emit('contact-candidate')"` on the button and `@contact-candidate.once="contactCandidate"` on the candidates HTML element to listen to the emitted event. The `.once` modifier helps towards our goal (with a client-side check) of not spamming the candidate with mails (if the company user would click the button more than once).

The callback function is defined in the main template (blade file) and added to the Vue component in `app.js`:

```js
methods: {
    contactCandidate: contactCandidate,
},
```

The implementation of that function should check the balance, send the request to contact the candidate (if balance allows), and show feedback to the user (error with reason or success message). I also want it to visually disable the button, so the user understands that `.once` modifier behaviour.


## Notes

- The database doesn't seem to have foreign key constraints set. It could be good practise to configure these.
- Images need alt tags
- We are leaking the all email addresses by blade template injection of the entire candidates object
