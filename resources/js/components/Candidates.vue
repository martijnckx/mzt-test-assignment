<template>
    <div class="mt-20">
        <div class="p-10">
            <h1 class="text-4xl font-bold">Candidates</h1>
        </div>
        <div class="p-10 grid grid-cols-1 sm:grid-cols-1 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-3 gap-5">
            <div v-for="candidate in candidates" class="rounded overflow-hidden shadow-lg" :key="candidate.id">
                <!-- Photos from https://thesecatsdonotexist.com/ -->
                <img class="w-full" :src="`https://d2ph5fj80uercy.cloudfront.net/05/cat${candidate.id}.jpg`" :alt="`Profile picture of ${candidate.name}`">
                <div class="px-6 py-4">
                    <div class="font-bold text-xl mb-2">{{candidate.name}}</div>
                    <p class="text-gray-700 text-base">{{candidate.description}}</p>
                </div>
                <div class="px-6 pt-4 pb-2"><span v-for="strength in JSON.parse(candidate.strengths)" class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2 mb-2" :key="strength + candidate.id">{{strength}}</span>
                </div>
                <div class="p-6 float-right">
                    <button @click="$emit('contact-candidate', candidate)" class="bg-white hover:bg-gray-100 text-gray-800 font-semibold py-2 px-4 border border-gray-400 rounded shadow">Contact {{ candidate.contacted ? 'again' : '' }}</button>
                    <button @click="$emit('hire-candidate', candidate)" class="bg-white hover:bg-gray-100 text-gray-800 font-semibold py-2 px-4 border border-gray-400 hover:bg-teal-100 rounded shadow disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-inherit" :disabled="!candidate.contacted">Hire</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['candidates'],
    emits: [
        'contact-candidate',
        'hire-candidate',
    ],
}
</script>
