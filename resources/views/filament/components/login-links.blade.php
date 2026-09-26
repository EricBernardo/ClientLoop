<nav class="mt-6 flex flex-col items-center gap-3 text-center text-sm" aria-label="Outras páginas">
    <p class="text-gray-500 dark:text-gray-400">
        Ainda não tem uma conta?
        <x-filament::link :href="route('register')">
            Cadastre-se
        </x-filament::link>
    </p>

    <x-filament::link :href="route('home')">
        Ir para a página inicial
    </x-filament::link>
</nav>
