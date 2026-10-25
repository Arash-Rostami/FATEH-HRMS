{
  "name": @json(config('app.name')),
  "short_name": @json(config('app.name_en')),
  "description": @json(config('app.slogan_en')),
  "start_url": "/",
  "scope": "/",
  "display": "standalone",
  "orientation": "portrait",
  "dir": "rtl",
  "lang": "fa",
  "background_color": @json(config('app.theme_header_bg')),
  "theme_color": @json(config('app.theme_primary')),
  "icons": [
    {
      "src": @json(asset(config('app.favicon'))),
      "sizes": "any",
      "type": "image/x-icon",
      "purpose": "any"
    }
  ]
}