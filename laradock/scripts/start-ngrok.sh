#!/bin/sh

# Проверяем, установлен ли NGROK_AUTHTOKEN
if [ -z "$NGROK_AUTHTOKEN" ]; then
  echo "Error: NGROK_AUTHTOKEN is not set."
  exit 1
fi

# Аутентификация ngrok
ngrok config add-authtoken $NGROK_AUTHTOKEN

# Запускаем ngrok в фоне
ngrok http --log=stdout --log-level info --log-format term --host-header=bot-panel.loc nginx:80 &
NGROK_PID=$!

# Ждем, пока ngrok запустится и создаст туннель
while ! curl --silent http://localhost:4040/api/tunnels > /dev/null; do
  sleep 1
done

# Получаем ссылку на туннель через API
TUNNEL_URL=$(curl --silent http://localhost:4040/api/tunnels | grep -Eo '"public_url":"https://[^"]+"' | head -n1 | sed 's/"public_url":"//g' | sed 's/"//g')

# Выводим ссылку на туннель
echo "Your ngrok tunnel is: $TUNNEL_URL"

# Оставляем контейнер работающим
wait $NGROK_PID
