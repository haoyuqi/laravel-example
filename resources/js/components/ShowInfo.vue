<style scoped>

</style>

<template>
    <div class="container">
        <div class="row justify-content-center bg-light" style="height: 500px">
            <div class="align-self-center">
                <h1 class="display-1">{{ info }}</h1>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                info: ''
            }
        },
        props: ['info']
    }
</script>

