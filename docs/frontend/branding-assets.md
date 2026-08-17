# Fouskee Concepts brand assets

## Canonical source

The exact user-supplied logo remains:

`frontend/public/branding/fouskee-concepts-logo.png`

It is preserved unchanged as the canonical source artwork.

## Presentation derivative

The application renders:

`frontend/public/branding/fouskee-concepts-logo-cropped.png`

This derivative contains the same source pixels with only excess outer ivory
canvas removed. The FC artwork, colors, proportions, and wordmark are untouched.

This lets the logo occupy appropriate visual weight in the sidebar, login,
loading, and responsive navigation contexts without redrawing or approximating
the brand.

## Rule

Do not recreate the FC mark with CSS, text, icons, or generated artwork.
Presentation derivatives may only remove unused outer canvas unless a future
brand decision explicitly approves another treatment.
