<label class="inline-flex items-center cursor-pointer group">
    <input 
        type="checkbox" 
        class="sr-only peer" 
        {{ $attributes }} 
    >
    <div class="relative w-11 h-6 
        /* Estado base (Off): Borde y fondo sutil */
        bg-input rounded-full transition-all duration-300
        border border-transparent
        
        /* Estado Focus: Usando la variable --ring */
        peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-ring peer-focus:ring-offset-2 peer-focus:ring-offset-background
        
        /* Estado Checked (On): Color primario Shadcn */
        peer-checked:bg-primary
        
        /* El 'punto' del switch (After) */
        after:content-[''] 
        after:absolute 
        after:top-[1px] 
        after:start-[2px] 
        after:bg-background 
        after:border-transparent 
        after:rounded-full 
        after:h-5 
        after:w-5 
        after:transition-all 
        after:duration-300
        after:shadow-sm
        
        /* Movimiento del punto */
        peer-checked:after:translate-x-full 
        rtl:peer-checked:after:-translate-x-full
    "></div>
    
    @if(isset($label))
        <span class="ms-3 text-sm font-medium text-foreground group-hover:text-foreground transition-colors">
            {{ $label }}
        </span>
    @endif
</label>