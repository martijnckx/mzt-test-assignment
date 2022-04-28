<template>
    <div class="fixed top-17 right-3 w-80 z-10">
        <div class="bg-slate-100 bg-red-100 bg-green-100 border-slate-400 border-red-400 border-green-400 text-slate-700 text-red-700 text-green-700 hidden"></div>
        <div :class="[`bg-${notification.colour}-100`,`border-${notification.colour}-400`,`text-${notification.colour}-700`]" class="border px-4 py-3 rounded my-2" role="alert" v-for="notification in notifications" :key="notification.id">
            <span class="block sm:inline">{{notification.message}}</span>
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
            }.bind(this), 5000);
        },
    },
}
</script>