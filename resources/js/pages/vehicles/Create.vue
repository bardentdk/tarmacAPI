<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/tarmac/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { create, index, store } from '@/routes/vehicles';
import type { Option } from '@/types/tarmac';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Mes véhicules', href: index() },
            { title: 'Ajouter', href: create() },
        ],
    },
});

defineProps<{
    options: {
        fuel: Option[];
        gearbox: Option[];
        drivetrain: Option[];
        tuning_stage: Option[];
    };
}>();

const selectClass =
    'h-11 w-full rounded-xl border border-input bg-card px-3.5 text-base outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';
</script>

<template>
    <Head title="Ajouter un véhicule" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Ajouter un véhicule"
            description="La masse et le niveau de prépa servent au calcul de puissance. Les réglages fins (CdA, rayon de roue) se font dans l'app."
        />

        <Form
            v-bind="store.form()"
            v-slot="{ errors, processing }"
            class="grid max-w-3xl gap-6 rounded-[20px] border bg-card p-5"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="name">Nom affiché</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        placeholder="Golf 7 GTI"
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="make">Marque</Label>
                    <Input
                        id="make"
                        name="make"
                        required
                        placeholder="Volkswagen"
                    />
                    <InputError :message="errors.make" />
                </div>
                <div class="grid gap-2">
                    <Label for="model">Modèle</Label>
                    <Input
                        id="model"
                        name="model"
                        required
                        placeholder="Golf GTI"
                    />
                    <InputError :message="errors.model" />
                </div>
                <div class="grid gap-2">
                    <Label for="year">Année</Label>
                    <Input
                        id="year"
                        name="year"
                        type="number"
                        inputmode="numeric"
                        placeholder="2017"
                    />
                    <InputError :message="errors.year" />
                </div>
                <div class="grid gap-2">
                    <Label for="engine">Moteur</Label>
                    <Input
                        id="engine"
                        name="engine"
                        placeholder="EA888 2.0 TSI"
                    />
                    <InputError :message="errors.engine" />
                </div>
                <div class="grid gap-2">
                    <Label for="fuel">Carburant</Label>
                    <select id="fuel" name="fuel" :class="selectClass" required>
                        <option
                            v-for="option in options.fuel"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <InputError :message="errors.fuel" />
                </div>
                <div class="grid gap-2">
                    <Label for="gearbox">Boîte</Label>
                    <select
                        id="gearbox"
                        name="gearbox"
                        :class="selectClass"
                        required
                    >
                        <option
                            v-for="option in options.gearbox"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <InputError :message="errors.gearbox" />
                </div>
                <div class="grid gap-2">
                    <Label for="drivetrain">Transmission</Label>
                    <select
                        id="drivetrain"
                        name="drivetrain"
                        :class="selectClass"
                        required
                    >
                        <option
                            v-for="option in options.drivetrain"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <InputError :message="errors.drivetrain" />
                </div>
                <div class="grid gap-2">
                    <Label for="tuning_stage">Niveau de prépa</Label>
                    <select
                        id="tuning_stage"
                        name="tuning_stage"
                        :class="selectClass"
                        required
                    >
                        <option
                            v-for="option in options.tuning_stage"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <InputError :message="errors.tuning_stage" />
                </div>
                <div class="grid gap-2">
                    <Label for="mass_kg">Masse à vide (kg)</Label>
                    <Input
                        id="mass_kg"
                        name="mass_kg"
                        type="number"
                        inputmode="numeric"
                        required
                        placeholder="1420"
                    />
                    <InputError :message="errors.mass_kg" />
                </div>
                <div class="grid gap-2">
                    <Label for="tire_size">Pneus</Label>
                    <Input
                        id="tire_size"
                        name="tire_size"
                        placeholder="225/40R18"
                    />
                    <InputError :message="errors.tire_size" />
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <Button type="submit" size="lg" :disabled="processing">
                    <Spinner v-if="processing" />
                    Ajouter le véhicule
                </Button>
                <Button variant="ghost" size="lg" as-child>
                    <Link :href="index()">Annuler</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
