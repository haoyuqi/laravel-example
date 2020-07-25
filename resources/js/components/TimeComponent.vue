<style scoped>

</style>

<template>
    <div class="container">
        <div class="row justify-content-center bg-light" style="height: 500px">
            <div class="align-self-center">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Time</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(item, key) in time_list">
                        <th scope="row">{{ key + 1 }}</th>
                        <td>{{ item }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                time_list: JSON.parse(this.init_data)
            }
        },
        props: ['init_data'],
        mounted() {
            Echo.channel('laravel_example_database_push-time')
                .listen('PushTimeEvent', (e) => {
                    this.time_list.push(e.time)
                })
        }
    }
</script>

