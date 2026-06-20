# Dummy Legacy 1.1.5

Эта ветка содержит legacy-сборку Dummy с поддержкой Minecraft Bedrock `1.1.5` / protocol `113`. Код поднят в корень репозитория: здесь нет вложенного каталога `113`, поэтому ветка используется так же, как обычная ветка ядра.

> Не публикуйте исходники, патчи, собранные `.phar`-файлы и приватные изменения вне условий вашей партнерки.

## Основное

- Ветка: `mcbe-1.1.5`.
- Версия ядра: `Dummy 5.39.1-dev`.
- Legacy-протокол: Minecraft Bedrock `1.1.5` / protocol `113`.
- Актуальный протокол ветки: Minecraft Bedrock `1.26.20` / protocol `975`.
- Мультиверсия: `1.1.5`, `1.16.20` - `1.26.20`.
- Зависимости устанавливаются через Composer. Каталог `vendor/` намеренно не хранится в Git.

Полный список поддерживаемых протоколов находится в `src/network/mcpe/protocol/ProtocolInfo.php`.

## Установка зависимостей

Требуется PHP `8.1+`, Composer `2.x` и расширения, необходимые PocketMine-MP. Минимальная проверка расширений выполняется при запуске ядра в `src/PocketMine.php`.

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
```

После установки Composer создаст локальный каталог `vendor/`. Он нужен для запуска и сборки, но не должен попадать в коммиты.

## Обновление ветки

```bash
git fetch origin
git checkout mcbe-1.1.5
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader
```

## Что важно для партнеров

- Эта ветка нужна только для серверов, которым требуется поддержка Minecraft Bedrock `1.1.5`.
- Для актуальной production-ветки используйте `main`.
- Не коммитьте `vendor/`, миры, логи, плагины, `.phar`-сборки и локальные конфиги.
- В баг-репорте указывайте ветку, commit hash, версию клиента Minecraft Bedrock, protocol ID и crashdump/log.

## Авторы и база

Dummy поддерживается командой Lunarix/Dummy.

Credits: PocketMine Team, NetherGamesMC, xvqrlz, Lunarelly, sheluvmyshe.

## Лицензия

Код, производный от PocketMine-MP, распространяется по условиям `LGPL-3.0-or-later`. Партнерские условия регулируют приватный доступ, поддержку и сопровождение, но не отменяют требований лицензий исходных проектов.

