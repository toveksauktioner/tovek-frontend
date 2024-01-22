Appearance module
  1. Overview

1. Overview
  1.1 What this module does
    The Appearance module is a module that is fed a .scss file and spits out a customized version of that file.
    When the customized file is imported in the beginning of the file, SCSS will use those variable values over the bootstrap variable values.
    The customized file simply overwrite the default variables (but before, according to the SCSS standard)

  1.2 Customizable templates
    A customizable template is a template file that has a file called "_bootstrap.scss" and "_custom.scss" in the template root css directory.
    By default, it is located in "/public_html/css/templates/TEMPLATE_NAME/_bootstrap.scss". and "/public_html/css/templates/TEMPLATE_NAME/_custom.scss"

    The bootstrap file must contain customizable variables and the custom file should exist, but be empty (the module write to this file).

2. Usage
  2.1 Quick start
    2.1.1. Create the file /public_html/css/templates/TEMPLATE_NAME/_bootstrap.scss. This is where you write code later.
    2.1.2. Create the file /public_html/css/templates/TEMPLATE_NAME/_custom.scss. This should be left empty.
    2.1.3. IN bootstrap, create a variable, enter: `$variabel: #ff0000;`
    2.1.4. Now make it customizable, change it to: `$variabel: #ff0000 !default;`
    2.1.5. The variable is now customizable. You may give it a prettier name by adding a comment, like this: `$variabel: #ff0000 !default; // Min variabel`
    2.1.6. Variables may be if different types. Types are defined within parenthesis. This is a color variable: `$variabel: #ff0000 !default; // Min variabel (color)`

3. Types
  Variables may be if different types. Types are defined within parenthesis.

  3.1. String
    May contain any string. Defined by not defining a type.
    Example: ``
  3.1. Color
    Use colorpicker
    Example: `(color)`
  3.2 Boolean
    Make the options "true" and "false" available
    Example: `(boolean)`
  3.3 Options
    Create custom options by defining them in a comma separated list. Values must be quoted.
    Example: `("inherit", "Option 1", "Option 2")`
  3.4 Images
    Enable image uploading. When admin upload a image, the varaible will contain the uploaded image path. May only contain a single path.
    Example: `(image)`

4. Technical explanation
  4.1. Swedish
    Appearance läser bootstrap såhär (per rad):
    1. Om raden innehåller en variabel och dess värde är angiven med !default
    Då blir variabeln redigerbar. Det angivna värdet är defaultvärdet
    2. Om en kommentar är angiven efter variabeldeklarationen, använd kommentaren som titel
    3. Kommentaren kan avslutas med en parantes. Inom parantesen anges vilken typ variabeln är
    3a. String: Då anges ingen parantes

    3b. Option: Ange en kommaseparerad lista med värden som man ska kunna välja. Varje värde omges av citationstecken
      `("inherit", "Open Sans", "Arial", "Lato")`
    3c. Boolean: Parantesen innhåller order `boolean` utan citationstecken. Skapar en optionmeny med de översatta värdena `true` och `false`
      `(boolean)`
    3d. color: Parantesen innehåller ordet `color`. Då får admin en colorpicker för detta fält
      `(color)`
    3e. image: Parantesen innehåller ordet `image`. Då blir fältet ett filuppladdningsfält där admin kan ladda upp en bild. Om en bild laddas upp så blir variabelns nya värde den uppladdade filens sökväg
      `(image)`

    4. Om en tom rad med en kommentar är angiven före en variabeln, lägg efterföljande i en grupp med samma titel som kommentarens text

    Man kan lokalisera Appearance i admin. Som default ligger den i supernovas meny. Under Verktyg > Utseende
    Även om den tekniska förklaringen verkar lång så är det inte alls så farligt som det ser ut:) Den är lång för att datorn ska tänka åt dig, så att utvecklanden ska slippa
