<template>
    <h1 class="text-2xl font-bold mb-4">Login</h1>
    <Form :resolver @submit="register" class="flex flex-col gap-4">
        <FormField v-slot="$field" as="section" name="firstName" initialValue="" class="flex flex-col gap-2">
            <InputText type="text" placeholder="First Name"/>
            <Message v-if="$field?.invalid" severity="error" size="small" variant="simple">{{
                    $field.error?.message
                }}
            </Message>
        </FormField>

        <FormField v-slot="$field" as="section" name="lastName" initialValue="" class="flex flex-col gap-2">
            <InputText type="text" placeholder="Last Name"/>
            <Message v-if="$field?.invalid" severity="error" size="small" variant="simple">{{
                    $field.error?.message
                }}
            </Message>
        </FormField>

        <FormField v-slot="$field" as="section" name="email" initialValue="" class="flex flex-col gap-2">
            <InputText type="text" placeholder="Email"/>
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
        <FormField v-slot="$field" asChild name="passwordConfirmation" initialValue="">
            <section class="flex flex-col gap-2">
                <Password type="text" placeholder="Confirm password" :feedback="false" toggleMask fluid/>
                <Message v-if="$field?.invalid" severity="error" size="small" variant="simple">{{
                        $field.error?.message
                    }}
                </Message>
            </section>
        </FormField>
        <Button type="submit" severity="secondary" label="Submit"/>
    </Form>

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
        firstName: yup.string().required().max(255),
        lastName: yup.string().required().max(255),
        email: yup.string().required('Email is required'),
        password: yup.string().required('Password is required'),
        passwordConfirmation: yup.string().required().oneOf([yup.ref('password'), null], 'Passwords must match')
    })
))

const register = async ({valid, values, error}) => {
    try {
       if(valid) {
           await auth.register({
               firstName: values.firstName,
               lastName: values.lastName,
               email: values.email,
               password: values.password,
               password_confirmation: values.passwordConfirmation
           })
       }
    } catch (err) {
        toast.add({ severity: 'danger', summary: err.message || 'Credenziali non valide', life: 3000 });
    }
}
</script>
