TrilobitPixabayBundle
==============================================

Mit der Pixabay Erweiterung können sie über die Dateiverwaltung von Contao Bilder oder Fotos von der freien Bilddatenbank Pixabay herunterladen. Um Pixabay benutzen zu können, benötigen sie eine API-Key, den sie nach der Registrierung bei Pixabay anfordern können. Sie können außerdem Voreinstellungen für die Pixabay-Suche in der Benutzerverwaltung festlegen.


With the Pixabay extension you can download images or photos from the free image database Pixabay via the file management of Contao. In order to use Pixabay, you will need an API key that you can request after registering on the Pixabay website. You can also set preferences for the Pixabay search in the User Management.


Backend Ausschnitt
------------

![Backend Ausschnitt](docs/images/contao-pixabay-bundle.png?raw=true "TrilobitPixabayBundle")


Installation
------------

Install the extension via composer: [trilobit-gmbh/contao-pixabay-bundle](https://packagist.org/packages/trilobit-gmbh/contao-pixabay-bundle).

And add the following code (with the API-Key from the Pixabay Website) to the config.yml of your project. You may have to create a config.yml, if it doesn't exist in your project. The config.yml is or has to be located in the app/config directory in Contao 4.4 and in the config directory in Contao 4.8.    

    contao:
      localconfig:
        pixabayApiKey: 'Your API-Key'
        pixabayImageSource: 'largeImageURL'


Compatibility
-------------

- Contao version ~4.4
- Contao version ~4.8
- Contao version ~4.9
