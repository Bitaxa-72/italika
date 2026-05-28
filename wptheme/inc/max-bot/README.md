# Italika MAX order bot

Модуль отправляет новые WooCommerce-заказы в активные чаты MAX. Логика такая же, как у Telegram-бота: чат включает уведомления командой `/start`, отключает командой `/stop`.

## 1. Создать бота в MAX

1. Откройте `business.max.ru` и войдите под аккаунтом организации.
2. Перейдите в раздел чат-ботов.
3. Создайте нового бота: укажите название, описание и данные, которые попросит платформа.
4. Дождитесь готовности/модерации, если MAX ее запросит.
5. В настройках бота откройте раздел интеграции и получите token.
6. Никому не отправляйте token и не вставляйте его в публичные файлы.

## 2. Записать токен локально

В корне проекта есть файл `.local-max.env`. Его можно использовать как локальную памятку:

```env
MAX_BOT_TOKEN=ваш_token_из_MAX
MAX_WEBHOOK_SECRET=случайная_строка_для_webhook
MAX_SITE_URL=https://selibox.com/
MAX_WEBHOOK_URL=https://selibox.com/wp-json/italika/v1/max/webhook
```

Файл добавлен в `.gitignore`. WordPress сам его не читает.

## 3. Добавить секреты на сайте

В production `wp-config.php` добавьте выше строки `/* That's all, stop editing! */`:

```php
define('ITALIKA_MAX_BOT_TOKEN', 'ваш_token_из_MAX');
define('ITALIKA_MAX_WEBHOOK_SECRET', 'та_же_строка_что_MAX_WEBHOOK_SECRET');
```

`ITALIKA_MAX_WEBHOOK_SECRET` должен состоять из `A-Z`, `a-z`, `0-9`, `_` или `-`, длина от 5 до 256 символов.

## 4. Зарегистрировать webhook

После добавления констант выполните:

```bash
curl -X POST "https://platform-api.max.ru/subscriptions" \
  -H "Authorization: YOUR_MAX_BOT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://selibox.com/wp-json/italika/v1/max/webhook","update_types":["message_created","bot_started"],"secret":"YOUR_MAX_WEBHOOK_SECRET"}'
```

Проверить подписки:

```bash
curl -X GET "https://platform-api.max.ru/subscriptions" \
  -H "Authorization: YOUR_MAX_BOT_TOKEN"
```

## 5. Подключить чат

1. Напишите боту `/start` в MAX.
2. Бот ответит, что включен.
3. После нового WooCommerce-заказа сообщение придет в этот чат.
4. Чтобы отключить чат, отправьте `/stop`.

## 6. Где лежат данные

- Активные MAX-чаты: WordPress option `italika_max_active_chats`.
- Маркер отправки по заказу: order meta `_italika_max_order_sent`.
- REST endpoint: `/wp-json/italika/v1/max/webhook`.
