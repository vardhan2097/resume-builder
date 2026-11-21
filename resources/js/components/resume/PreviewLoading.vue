<template>
    <div
        class="flex min-h-60 flex-col items-center justify-center gap-5 p-6"
        role="status"
    >
        <div class="document" aria-hidden="true">
            <div class="document-header"></div>
            <div class="document-line document-line--short"></div>
            <div class="document-line"></div>
            <div class="document-line"></div>
            <div class="document-line document-line--medium"></div>
        </div>
        <p class="text-sm text-muted-foreground">Preparing preview…</p>
    </div>
</template>

<style scoped>
.document {
    display: flex;
    width: 76px;
    height: 100px;
    flex-direction: column;
    gap: 8px;
    padding: 15px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: white;
    box-shadow: 0 4px 12px rgb(15 23 42 / 8%);
}

.document-header,
.document-line {
    height: 4px;
    flex-shrink: 0;
    border-radius: 4px;
    background: #cbd5e1;
    animation: reveal 2s ease-in-out infinite both;
}

.document-header {
    width: 55%;
    height: 6px;
    background: #0f766e;
}

.document-line--short {
    width: 45%;
    animation-delay: 0.15s;
}
.document-line:nth-child(3) {
    animation-delay: 0.3s;
}
.document-line:nth-child(4) {
    animation-delay: 0.45s;
}
.document-line--medium {
    width: 70%;
    animation-delay: 0.6s;
}

@keyframes reveal {
    0%,
    10% {
        opacity: 0.25;
        transform: scaleX(0.35);
        transform-origin: left;
    }
    35%,
    80% {
        opacity: 1;
        transform: scaleX(1);
        transform-origin: left;
    }
    100% {
        opacity: 0.25;
        transform: scaleX(1);
        transform-origin: left;
    }
}

@media (prefers-reduced-motion: reduce) {
    .document-header,
    .document-line {
        animation: none;
    }
}
</style>
