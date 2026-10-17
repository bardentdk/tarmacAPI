<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Supprimer mon compte"
            description="Supprime ton compte et toutes tes données (RGPD)"
        />
        <div
            class="space-y-4 rounded-2xl border border-danger-border bg-danger-bg p-4"
        >
            <div class="relative space-y-0.5 text-danger">
                <p class="font-medium">Attention</p>
                <p class="text-sm">
                    Attention : c'est définitif, on ne pourra rien récupérer.
                </p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive" data-test="delete-user-button"
                        >Supprimer mon compte</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle
                                >Tu veux vraiment supprimer ton compte
                                ?</DialogTitle
                            >
                            <DialogDescription>
                                Tes véhicules, tirs, codes défauts, trajets et
                                liens de partage seront supprimés
                                définitivement. Saisis ton mot de passe pour
                                confirmer.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only"
                                >Mot de passe</Label
                            >
                            <PasswordInput
                                id="password"
                                name="password"
                                ref="passwordInput"
                                placeholder="Mot de passe"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    Annuler
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                Supprimer mon compte
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
