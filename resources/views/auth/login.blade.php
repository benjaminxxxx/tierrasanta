<x-guest-layout>
    <!-- Contenedor principal que centra la tarjeta y aplica padding -->
    <div class="max-w-5xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
        <x-card
            class="flex flex-col lg:flex-row w-full shadow-xl rounded-xl overflow-hidden !p-0">
            <!-- Sección de Imagen (oculta en pantallas pequeñas, toma el espacio restante en grandes) -->
            <div class="bg-stone-200 hidden lg:block flex-1 p-8 flex items-center justify-center">
                <img src="{{asset('images/logo/logo.svg')}}" class="mx-auto w-full max-w-md h-auto object-contain"
                    alt="illustration" />
            </div>

            <!-- Sección del Formulario (ancho completo en móvil, 25rem en pantallas grandes) -->
            <div class="w-full lg:w-[25rem] p-8 sm:p-10 lg:p-12 border-t lg:border-t-0 lg:border-l border-border">
                <div class="w-full">
                    <h2 class="mb-2 text-xl font-bold text-card-foreground text-center">
                        Bienvenido al Sistema Administrativo
                    </h2>
                    <span class="mb-8 block text-lg font-medium text-card-foreground text-center">Inicia Sesión en tu Cuenta</span>

                    <x-validation-errors class="mb-4 text-red-600 dark:text-red-400" />

    
                    <form method="POST" action="{{ route('login') }}" class="px-6">
                        @csrf
                        <div class="mb-5">
                            <x-label>Email</x-label>
                            <div class="relative mt-1">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none text-gray-400 dark:text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                </div>
                                <x-input type="email" name="email" :value="old('email')" required autofocus
                                    autocomplete="username" placeholder="Ingrese su email" class="pl-10" />
                            </div>
                        </div>
                        <div class="mb-6">
                            <x-label>Contraseña</x-label>
                            <div class="relative mt-1">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none text-gray-400 dark:text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </div>
                                <x-input type="password" name="password" required autocomplete="current-password"
                                    placeholder="Ingrese su contraseña" class="pl-10" />    
                            </div>
                        </div>
                        <div class="block mt-6">
                            <label for="remember_me" class="flex items-center">
                                <x-checkbox id="remember_me" name="remember" />
                                <span class="ms-2 text-sm text-card-foreground">Recordar sesión</span>
                            </label>
                        </div>
                        <div class="mt-6">
                            <x-button type="submit" class="w-full">
                                Ingresar
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </x-card>
    </div>
</x-guest-layout>