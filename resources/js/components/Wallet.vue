<template>
     <div class="w-full p-6 bg-teal-100 text-right font-bold">Your wallet has {{coins}} coins</div>
</template>

<script>
export default {
    props: {
        'coins': Number,
        data() {
            return {
                coins: this.coins,
            }
        },
    },
}
</script>