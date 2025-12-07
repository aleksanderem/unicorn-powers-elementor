# Unicorn Powers for Elementor

Zaawansowane efekty wizualne dla kontenerów Elementora - spotlight, animated border, beam, conic glow z inner glow i animacją kolorów.

## Instalacja

1. Pobierz plugin z GitHub Releases
2. Zainstaluj przez WordPress Admin > Pluginy > Dodaj nowy > Wgraj wtyczkę
3. Aktywuj plugin

## Dostępne efekty

### 1. Spotlight
Gradient podążający za kursorem wewnątrz karty.

**Użycie:** Idealne dla kart produktów, feature boxes, testimoniali.

### 2. Animated Border
Animowana obramówka z rotującym gradientem.

**Użycie:** CTA buttony, wyróżnione sekcje, karty premium.

### 3. Beam
Efekt świetlny "beam" przesuwający się po krawędzi.

**Użycie:** Loading states, aktywne elementy, hover effects.

### 4. Conic Glow (Aceternity-style)
Stożkowy gradient podążający za kursorem z:
- **Idle border** - subtelna obramówka gdy kursor jest poza kartą
- **Inner glow** - wewnętrzny blask przy krawędzi najbliższej kursorowi
- **Color cycling** - płynna animacja między kolorami

**Użycie:** Premium cards, pricing tables, interactive dashboards.

## Przykłady Real-Life

### Pricing Card

```
Container Settings:
- Magic Card: Enabled
- Effect Mode: Conic Glow
- Conic Gradient Colors: #3b82f6, #8b5cf6, #ec4899
- Inner Glow: Enabled
- Inner Glow Colors: rgba(59,130,246,0.3), rgba(139,92,246,0.3), rgba(236,72,153,0.3)
- Inner Glow Size: 200px
- Inner Glow Blur: 30px
- Idle Border Opacity: 0.3
- Border Radius: 24px
- Border Width: 2px
```

### Feature Card Grid

```
Parent Container Settings:
- Magic Card: Enabled
- Apply to: Children containers
- Effect Mode: Conic Glow
- Easing: 0.15 (smooth follow)
- Glow Persists: Yes (stays after hover)

Child Container Settings:
- Background: rgba(0,0,0,0.5)
- Border Radius: 16px
- Padding: 32px
```

### Product Showcase

```
Container Settings:
- Magic Card: Enabled
- Effect Mode: Spotlight
- Spotlight Always On: Yes
- Default Position: Center (50%, 50%)
- Gradient Color: rgba(255,255,255,0.1)
- Blob Size: 300px
- Tilt Effect: Enabled
- Tilt Intensity: 5
```

### CTA Button Container

```
Container Settings:
- Magic Card: Enabled
- Effect Mode: Animated Border
- Gradient Colors: #f59e0b, #ef4444, #ec4899, #8b5cf6
- Animation Speed: 3s
- Border Width: 2px
- Border Radius: 12px
```

### Dashboard Widget

```
Container Settings:
- Magic Card: Enabled
- Effect Mode: Conic Glow
- Conic Gradient: Single color #3b82f6
- Idle Border: 0.5 opacity
- Inner Glow: Enabled
- Inner Glow Color: rgba(59,130,246,0.2)
- Inner Glow Size: 150px
- Always Visible: Yes
```

### Testimonial Card

```
Container Settings:
- Magic Card: Enabled
- Effect Mode: Conic Glow
- Conic Gradient Colors: #10b981, #06b6d4
- Inner Glow Colors: rgba(16,185,129,0.25), rgba(6,182,212,0.25)
- Color Animation Speed: 8s
- Easing: 0.1 (very smooth)
- Glow Persists: No
```

### Image Gallery Item

```
Container Settings:
- Magic Card: Enabled
- Effect Mode: Spotlight
- Gradient Color: rgba(255,255,255,0.15)
- Blob Size: 250px
- Tilt: Enabled
- Tilt Intensity: 8
```

### Service Card with Icon

```
Container Settings:
- Magic Card: Enabled
- Effect Mode: Conic Glow
- Apply to: Self
- Conic Gradient: Brand colors
- Inner Glow: Enabled
- Inner Glow Blur: 40px (extra soft)
- Border Radius: 20px
```

## Tryby aplikacji efektu

### Self Mode
Efekt aplikowany bezpośrednio na kontener.

### Children Mode
Efekt aplikowany na wszystkie bezpośrednie dzieci kontenera. Idealne dla gridów kart.

**Exclude Class:** Możesz wykluczyć elementy dodając klasę CSS (np. `no-magic`).

## Tips & Tricks

1. **Dla lepszej wydajności** - używaj `Glow Persists: No` gdy masz wiele kart
2. **Subtelny efekt** - ustaw niską opacity idle border (0.1-0.2) i mały inner glow
3. **Dramatyczny efekt** - użyj jasnych kolorów, dużego inner glow (200px+) i wysokiego blur (40px+)
4. **Smooth animation** - ustaw easing na 0.05-0.1 dla bardzo płynnego śledzenia kursora
5. **Color cycling** - dodaj 3-5 kolorów i ustaw speed na 5-10s dla subtelnej animacji

## Changelog

### v1.0.0
- Initial release
- Spotlight effect
- Animated border effect
- Beam effect
- Conic glow effect (Aceternity-style)
- Inner glow with color cycling animation
- Tilt effect
- Children mode for grids
- Elementor integration with visual controls
- GitHub auto-updates
