# Fouskee Concepts brand asset

## Canonical application logo

The canonical application logo is:

`frontend/public/branding/fouskee-concepts-logo.png`

This file is the exact user-approved Fouskee Concepts artwork supplied for the
project. UI components must use this asset rather than recreating, approximating,
or substituting the FC monogram in code.

## Usage

`BrandMark.tsx` is the shared presentation component for the login experience,
loading state, and authenticated navigation. It references the canonical public
asset directly and only changes rendered size through CSS.

Do not redraw the logo with text, CSS shapes, icon libraries, or generated
artwork unless a future brand decision explicitly replaces the canonical asset.
