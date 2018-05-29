<template>
    <div class="dashboard-container">
        <component :is="currentRole"></component>
    </div>
</template>

<script>
    import { mapGetters } from 'vuex'
    import merchantDashboard from './admin'

    export default {
        name: 'home',
        components: { merchantDashboard },
        data () {
            return {
                currentRole: 'merchantDashboard'
            }
        },
        computed: {
            ...mapGetters([
                'roles'
            ])
        },
        created () {
            if (!this.roles.includes('merchant')) {
                this.currentRole = 'editorDashboard'
            }
        }
    }
</script>
