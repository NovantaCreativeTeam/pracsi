<template>
    <div class="flex flex-col items-center mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Accedi</h1>
    </div>
    <Form :resolver @submit="handleLogin" class="flex flex-col gap-4">
        <FormField v-slot="$field" as="section" name="email" initialValue="" class="flex flex-col gap-2">
            <InputText type="text" placeholder="Email or Username"/>
            <Message v-if="$field?.invalid" severity="error" size="small" variant="simple">{{
                    $field.error?.message
                }}
            </Message>
        </FormField>
        <FormField v-slot="$field" asChild name="password" initialValue="">
            <section class="flex flex-col gap-2">
                <Password type="text" placeholder="Password" :feedback="false" toggleMask fluid/>
                <Message v-if="$field?.invalid" severity="error" size="small" variant="simple">{{
                        $field.error?.message
                    }}
                </Message>
            </section>
        </FormField>
        <Button type="submit" severity="primary" label="Accedi"/>
    </Form>
    <div class="flex flex-col items-center mt-6 text-center">
        <small>Se non possiedi ancora un account puoi richiederlo contattandoci a: <a class="font-bold" href="mailto:stefania.ferrari@uniupo.it">stefania.ferrari@uniupo.it</a></small>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import {useAuthStore} from '../../stores/auth'
import {useRouter} from 'vue-router'
import { useToast } from "primevue/usetoast";
import { yupResolver } from '@primevue/forms/resolvers/yup';
import * as yup from 'yup'

const auth = useAuthStore()
const router = useRouter()

const toast = useToast()

const resolver = ref(yupResolver(
    yup.object().shape({
        email: yup.string().required('Email is required'),
        password: yup.string().required('Password is required'),
    })
))

const handleLogin = async ({valid, values, error}) => {
    try {
        await auth.login(values)
        toast.add({ severity: 'success', summary: 'Login successfull', life: 3000 });
        router.push('/')
    } catch (err) {
        toast.add({ severity: 'danger', summary: err.message || 'Credenziali non valide', life: 3000 });
    }
}
</script>
