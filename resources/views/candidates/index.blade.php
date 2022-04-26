<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
      <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">

     <title>MZT test assignment</title>

        <!-- Fonts -->
 <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;600;700&display=swap" rel="stylesheet">

      <style>
       body {
    font-family: 'Roboto', sans-serif;
  }
   </style>

  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    </head>
    <body>
    <div id="app">
 <div class="w-full p-6 bg-teal-100 text-right font-bold">Your wallet has: {{$coins ?? '?' }} coins</div>
    <candidates @contact-candidate.once="contactCandidate" :candidates="{{ json_encode($candidates) }}">
</candidates>
    </div>

    <script>
       const coins = {{$coins ?? 0}};
       function contactCandidate() {
          // @todo
          // check if you have enough coins
          // send post request to contact candidate
          // show feedback to the user (error with reason / success)
          // disable the contact button, since you can only press it once → note "already contacted" (?)
          console.log(`FOO BAR! ${coins}`);
       }
    </script>
    <script src="{{ mix('/js/app.js') }}"></script>
    </body>
</html>
