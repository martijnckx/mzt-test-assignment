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
   <div id="app" class="container mx-auto relative">
      <notifications ref="notifications"></notifications>
      <wallet :coins="coins"></wallet>
      <candidates @contact-candidate="contactCandidate" @hire-candidate="hireCandidate" :candidates="candidates">
      </candidates>
   </div>

   <script>
      let coins = {{ $coins ?? 0}};
      let candidates = {!! json_encode($candidates, JSON_HEX_TAG) !!};
      
      function updateCandidate(candidate) {
         for (let i = 0; i < this.candidates.length; i++) {
            if (this.candidates[i].id === candidate.id) {
               this.candidates[i].contacted = candidate.contacted;
            }
         }
      }

      function removeCandidate(candidate) {
         for (let i = 0; i < this.candidates.length; i++) {
            if (this.candidates[i].id === candidate.id) {
               this.candidates.splice(i, 1);
            }
         }
      }

      function hireCandidate(candidate) {
         if (!candidate.contacted) {
            this.$refs.notifications.showNotification('Error: candidate must be contacted first', 'negative');
            return;
         }

         this.$refs.notifications.showNotification(`Hiring ${candidate.name}...`);

         fetch('/candidates-hire', {
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
                  candidate.contacted = true;
                  candidate.hired = true;
                  this.coins = data.coins;
                  this.removeCandidate(candidate);
                  this.$refs.notifications.showNotification(`${candidate.name} has been hired!`, 'positive');
               } else {
                  this.$refs.notifications.showNotification(`Error: ${data.message}`, 'negative');

               }
            });
      }

      function contactCandidate(candidate) {
         const costOfContact = {{ $costOfContact }};

         if (this.coins < costOfContact && !candidate.contacted) {
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
                  candidate.contacted = true;
                  this.coins = data.coins;
                  this.updateCandidate(candidate);
                  this.$refs.notifications.showNotification(`${candidate.name} has been contacted!`, 'positive');
               } else {
                  this.$refs.notifications.showNotification(`Error: ${data.message}`, 'negative');

               }
            });
      }
   </script>
   <script src="{{ mix('/js/app.js') }}"></script>
</body>

</html>