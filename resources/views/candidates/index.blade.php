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
      <notifications ref="notifications"></notifications>
      <wallet :coins="coins"></wallet>
      <candidates @contact-candidate="contactCandidate" :candidates="{{ json_encode($candidates) }}">
      </candidates>
   </div>

   <script>
      let coins = {{ $coins ?? 0}};
      function contactCandidate(candidate) {
         // @todo disable the contact button, since you can only press it once → note "already contacted" (?)

         const costOfContact = {{ $costOfContact }};

         if (this.coins < costOfContact) {
            this.$refs.notifications.showNotification('Error: insufficient coins', 'negative');
            return;
         }
         this.$refs.notifications.showNotification(`Contacting ${candidate.name}...`);

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
               if (data.status === 'success') {
                  this.coins = data.coins;
                  this.$refs.notifications.showNotification('Candidate has been contacted!', 'positive')
               } else {
                  this.$refs.notifications.showNotification(`Error: ${data.message}`, 'negative')

               }
            });
      }
   </script>
   <script src="{{ mix('/js/app.js') }}"></script>
</body>

</html>