<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Camera, Trash, Upload, User as UserIcon } from '@lucide/vue';
import { onBeforeUnmount, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import 'cropperjs/dist/cropper.css';

type Props = {
    patientId: number;
    initialPhotoUrl: string | null;
    uploadUrl: string;
    destroyUrl: string;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'updated', photoUrl: string | null): void;
}>();

const page = usePage();
const photoUrl = ref<string | null>(props.initialPhotoUrl);
const fileInput = ref<HTMLInputElement | null>(null);
const cropImage = ref<HTMLImageElement | null>(null);
const cropper = ref<unknown>(null);

const cropDialogOpen = ref(false);
const pendingFile = ref<File | null>(null);
const previewSrc = ref<string | null>(null);
const cropError = ref<string | null>(null);
const isUploading = ref(false);
const isRemoving = ref(false);

const ACCEPT = 'image/png,image/jpeg';
const MAX_BYTES = 5 * 1024 * 1024;

watch(
    () => props.initialPhotoUrl,
    (value) => {
        photoUrl.value = value;
    },
);

watch(
    () =>
        (page.props as { flash?: { patientPhotoUrl?: string | null } }).flash
            ?.patientPhotoUrl,
    (value) => {
        if (value === undefined) {
            return;
        }

        photoUrl.value = value;
        emit('updated', value);
    },
);

function openFilePicker() {
    cropError.value = null;
    fileInput.value?.click();
}

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) {
        return;
    }

    target.value = '';

    if (file.size > MAX_BYTES) {
        cropError.value = 'La imagen no puede pesar más de 5 MB.';

        return;
    }

    if (!['image/png', 'image/jpeg'].includes(file.type)) {
        cropError.value = 'Solo se permiten imágenes en formato PNG o JPG.';

        return;
    }

    pendingFile.value = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        previewSrc.value = e.target?.result as string;
        cropDialogOpen.value = true;
    };
    reader.readAsDataURL(file);
}

function initCropper() {
    if (!cropImage.value) {
        return;
    }

    destroyCropper();

    import('cropperjs').then(({ default: Cropper }) => {
        if (!cropImage.value) {
            return;
        }

        cropper.value = new Cropper(cropImage.value, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 0.9,
            background: false,
            movable: true,
            zoomable: true,
            rotatable: false,
            scalable: false,
            responsive: true,
            minContainerWidth: 320,
            minContainerHeight: 320,
        }) as unknown;
    });
}

function destroyCropper() {
    const c = cropper.value as { destroy?: () => void } | null;

    if (c && typeof c.destroy === 'function') {
        c.destroy();
    }

    cropper.value = null;
}

watch(cropDialogOpen, (open) => {
    if (open) {
        requestAnimationFrame(initCropper);
    } else {
        destroyCropper();
        previewSrc.value = null;
        pendingFile.value = null;
    }
});

function closeCropDialog() {
    cropDialogOpen.value = false;
}

function setPhotoUrl(value: string | null) {
    photoUrl.value = value;
    emit('updated', value);
}

async function applyCrop() {
    const c = cropper.value as {
        getCroppedCanvas: (opts: object) => HTMLCanvasElement;
    } | null;

    if (!c) {
        return;
    }

    isUploading.value = true;
    cropError.value = null;

    try {
        const canvas = c.getCroppedCanvas({
            width: 512,
            height: 512,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        if (!canvas) {
            cropError.value = 'No se pudo recortar la imagen.';

            return;
        }

        const blob: Blob | null = await new Promise((resolve) =>
            canvas.toBlob(resolve, 'image/jpeg', 0.85),
        );

        if (!blob) {
            cropError.value = 'No se pudo generar la imagen recortada.';

            return;
        }

        const formData = new FormData();
        const filename = pendingFile.value?.name ?? 'photo.jpg';
        formData.append('photo', blob, filename);

        router.post(props.uploadUrl, formData, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (pageResponse) => {
                const flashed = (
                    pageResponse.props as {
                        flash?: { patientPhotoUrl?: string | null };
                    }
                ).flash?.patientPhotoUrl;
                const newUrl = flashed ?? photoUrl.value;
                setPhotoUrl(newUrl ?? null);
                cropDialogOpen.value = false;
            },
            onError: (errors) => {
                cropError.value =
                    (errors.photo as string | undefined) ??
                    'Error al subir la imagen.';
            },
            onFinish: () => {
                isUploading.value = false;
            },
        });
    } catch {
        cropError.value = 'Ocurrió un error inesperado al procesar la imagen.';
        isUploading.value = false;
    }
}

function removePhoto() {
    isRemoving.value = true;

    router.delete(props.destroyUrl, {
        preserveScroll: true,
        onSuccess: (pageResponse) => {
            const flashed = (
                pageResponse.props as {
                    flash?: { patientPhotoUrl?: string | null };
                }
            ).flash?.patientPhotoUrl;
            setPhotoUrl(flashed ?? null);
        },
        onFinish: () => {
            isRemoving.value = false;
        },
    });
}

onBeforeUnmount(() => {
    destroyCropper();

    if (previewSrc.value) {
        URL.revokeObjectURL(previewSrc.value);
    }
});
</script>

<template>
    <div class="flex flex-col items-start gap-3">
        <span class="text-sm font-medium">Fotografía</span>
        <div class="flex items-center gap-4">
            <div
                class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border border-border bg-muted text-muted-foreground"
            >
                <img
                    v-if="photoUrl"
                    :src="photoUrl"
                    alt="Foto del paciente"
                    class="h-full w-full object-cover"
                />
                <UserIcon v-else class="h-8 w-8" />
            </div>
            <div class="flex flex-col gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="openFilePicker"
                >
                    <Camera class="mr-2 h-4 w-4" />
                    {{ photoUrl ? 'Cambiar foto' : 'Subir foto' }}
                </Button>
                <Button
                    v-if="photoUrl"
                    type="button"
                    variant="ghost"
                    size="sm"
                    :disabled="isRemoving"
                    @click="removePhoto"
                >
                    <Trash class="mr-2 h-4 w-4" />
                    {{ isRemoving ? 'Eliminando...' : 'Eliminar foto' }}
                </Button>
            </div>
        </div>
        <InputError :message="cropError ?? undefined" />
        <p class="text-xs text-muted-foreground">
            Formatos permitidos: PNG o JPG. Tamaño máximo: 5 MB. La imagen se
            recortará a 512×512 px.
        </p>

        <input
            ref="fileInput"
            type="file"
            :accept="ACCEPT"
            class="hidden"
            @change="onFileChange"
        />

        <Dialog v-model:open="cropDialogOpen">
            <DialogContent class="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Recortar Fotografía</DialogTitle>
                    <DialogDescription>
                        Ajusta el encuadre y recorta la imagen a 512×512 px
                        (calidad JPEG 85%).
                    </DialogDescription>
                </DialogHeader>
                <div
                    class="my-4 flex items-center justify-center bg-muted/30"
                    style="min-height: 360px; max-height: 50vh"
                >
                    <img
                        v-if="previewSrc"
                        ref="cropImage"
                        :src="previewSrc"
                        alt="Imagen a recortar"
                        class="max-h-[50vh] max-w-full"
                    />
                </div>
                <InputError :message="cropError ?? undefined" />
                <DialogFooter>
                    <Button
                        type="button"
                        variant="secondary"
                        :disabled="isUploading"
                        @click="closeCropDialog"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        :disabled="isUploading"
                        @click="applyCrop"
                    >
                        <Upload v-if="!isUploading" class="mr-2 h-4 w-4" />
                        <Spinner v-else class="mr-2" />
                        {{ isUploading ? 'Subiendo...' : 'Recortar y guardar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
