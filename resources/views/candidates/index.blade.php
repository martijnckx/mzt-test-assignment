<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
   <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <meta name="csrf-token" content="{{ csrf_token() }}" />

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
      <wallet :coins="coins"></wallet>
      <candidates @contact-candidate="contactCandidate" :candidates="{{ json_encode($candidates) }}">
      </candidates>
   </div>

   <script>
      let coins = {{ $coins ?? 0}};
      function contactCandidate(candidate) {
         // @todo
         // show feedback to the user (error with reason / success)
         // disable the contact button, since you can only press it once → note "already contacted" (?)

         // This comes from the server so it only needs to be changed once when the cost changes in the future
         const costOfContact = {{ $costOfContact }};

         if (this.coins < costOfContact) {
            alert('not enough coins'); //temp
            return;
         }

         fetch('/candidates-contact', {
            method: 'POST',
            headers: {
               'Content-Type': 'application/json',
               'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
               candidate: candidate.id,
            }),
         })
            .then(response => response.json())
            .then(data => {
               this.coins -= costOfContact;
               console.log(data)
            });

         console.log(`You now have ${coins} coins`);
         console.log('Contacting candidate...');
         console.log(candidate);
      }
   </script>
   <script src="{{ mix('/js/app.js') }}"></script>
</body>

</html>