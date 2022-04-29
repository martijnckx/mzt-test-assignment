<template>
        <div class="fixed top-17 right-3 w-full z-10">
            <div class="container mx-auto relative">
                <div class="w-80 absolute top-0 right-0">
                    <div class="bg-slate-100 bg-red-100 bg-green-100 border-slate-400 border-red-400 border-green-400 text-slate-700 text-red-700 text-green-700 hidden"></div>
                    <div :class="[`bg-${notification.colour}-100`,`border-${notification.colour}-400`,`text-${notification.colour}-700`]" class="border px-4 py-3 rounded my-2" role="alert" v-for="notification in notifications" :key="notification.id">
                        <span class="block sm:inline">{{notification.message}}</span>
                    </div>
                </div>
            </div>
        </div>
</template>

<script>
export default {
    data: function() {
        return {
            notifications: [],
        }
    },
    methods: {
        showNotification(message, style = 'neutral') {
            const colours = {
                neutral: 'slate',
                positive: 'green',
                negative: 'red',
            };

            if (!style in colours) style = 'neutral';

            this.notifications.push({
                id: `${Date.now()}${Math.floor(Math.random() * 100)}`,
                message: message,
                colour: colours[style],
            });

            setTimeout(function() {
                this.notifications.shift();
            }.bind(this), 2000);

            // This source mentions 6 seconds as an appropriate accessible time to display notifications:
            // https://sheribyrnehaber.medium.com/designing-toast-messages-for-accessibility-fb610ac364be#:~:text=A%20good%20length%20of%20time,best%20practice%20is%206%20seconds.
            // But for this test app it is easier to work with 2 seconds. Readable, but not taking up space for too long 😄.
        },
    },
}
</script>