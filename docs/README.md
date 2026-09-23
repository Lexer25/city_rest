# REST-модуль

Универсальный REST-каркас для Kohana 3.3.

## Возможности

- JWT-аутентификация (HS256)
- Универсальный формат ответа: `{ok, data, meta}` / `{ok, error}`
- Справочник кодов ошибок `Rest_Error`
- Пагинация через `Rest_Pagination`
- Хук аудита `Rest_Audit`
- CORS из конфига
- Базовый контроллер `Controller_Rest_Base`

## Подключение

В `application/bootstrap.php`:

```php
Kohana::modules(array(
    'rest'   => MODPATH.'rest',
    'parsec' => MODPATH.'parsec',
));
```
