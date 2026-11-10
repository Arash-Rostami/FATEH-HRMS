import profile from '../data/profile.js'
import registerModuleData from '../module-runtime.js'

const signCodeCard = () => ({
    open: false,

    toggle() {
        this.open = !this.open
    },
})

registerModuleData({ profile, signCodeCard })
