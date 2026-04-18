<script lang="ts" setup>
import { computed } from 'vue'
import { useLangStore } from '@/stores/lang'
import { useTranslate } from '@/composables/useTranslate'

const langStore = useLangStore()
const t = useTranslate()

const localeLabel = computed(() => langStore.resolvedLocale.toUpperCase())
</script>

<template>
    <Transition name="lang-overlay">
        <div
            v-if="langStore.loading"
            class="lang-overlay"
            role="status"
            aria-live="polite"
        >
            <div class="lang-overlay__card">
                <div class="lang-overlay__spinner-wrap">
                    <v-progress-circular
                        color="primary"
                        indeterminate
                        size="56"
                        width="4"
                    />
                    <span class="lang-overlay__locale">{{ localeLabel }}</span>
                </div>
                <div class="lang-overlay__text">
                    <div class="lang-overlay__title">{{ t('changingLanguage') }}</div>
                    <div class="lang-overlay__subtitle">{{ t('pleaseWait') }}</div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.lang-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(var(--v-theme-surface), 0.88);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}

.lang-overlay__card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    padding: 28px 40px;
    background: rgb(var(--v-theme-surface));
    border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    animation: lang-card-pop 0.3s ease-out both;
}

.lang-overlay__spinner-wrap {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 72px;
    height: 72px;
}

.lang-overlay__locale {
    position: absolute;
    font-size: 0.875rem;
    font-weight: 700;
    color: rgb(var(--v-theme-primary));
    letter-spacing: 0.04em;
}

.lang-overlay__text {
    text-align: center;
}

.lang-overlay__title {
    font-size: 0.95rem;
    font-weight: 600;
    color: rgb(var(--v-theme-on-surface));
}

.lang-overlay__subtitle {
    font-size: 0.8rem;
    color: rgba(var(--v-theme-on-surface), 0.6);
    margin-top: 2px;
}

@keyframes lang-card-pop {
    from {
        opacity: 0;
        transform: scale(0.92);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Enter / Leave transition untuk overlay */
.lang-overlay-enter-active,
.lang-overlay-leave-active {
    transition: opacity 0.25s ease, backdrop-filter 0.25s ease;
}

.lang-overlay-enter-from,
.lang-overlay-leave-to {
    opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
    .lang-overlay__card {
        animation: none;
    }
}
</style>
