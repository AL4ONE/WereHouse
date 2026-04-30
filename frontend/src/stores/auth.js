import api from "@/api";
import { defineStore } from "pinia";
import { computed, ref } from "vue";

export const useAuthStore = defineStore("auth", () => {
    const token = ref(localStorage.getItem("token"))
    const user = ref(JSON.parse(localStorage.getItem("user") || "null"))
    const isLoggedIn = computed(() => !!token.value)
    const userRole = computed(() => user.value?.role)

    async function register(name, email, password, password_confirmation) {
        const response = await api.post("/register", { name, email, password, password_confirmation })
        token.value = response.data.token
        user.value = response.data.data

        localStorage.setItem("token", token.value)
        localStorage.setItem("user", JSON.stringify(user.value))
    }

    async function login(email, password) {
        const response = await api.post("/login", { email, password })
        token.value = response.data.token
        user.value = response.data.data

        localStorage.setItem("token", token.value)
        localStorage.setItem("user", JSON.stringify(user.value))
    }

    async function logout() {
        try {
            await api.post("/logout")
        } catch (e) {
            console.log(e)
        }
        token.value = null
        user.value = null
        localStorage.removeItem("token")
        localStorage.removeItem("user")
        window.location.reload()
    }

    return { token, user, register, login, logout, isLoggedIn, userRole }
})