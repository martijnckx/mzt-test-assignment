<template>
     <div class="fixed top-0 left-0 w-full p-6 bg-teal-100 text-right font-bold">
         <div class="container mx-auto">
            Your wallet has {{coins}} coins
         </div>
    </div>
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