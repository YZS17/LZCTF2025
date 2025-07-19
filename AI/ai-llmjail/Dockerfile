FROM node:20-alpine

WORKDIR /app

ENV NOVITA_API_KEY=sk_ceB46ZBdon6SGm2dQHeHSVHfSpmghj9IOR10W2mY6Ao

COPY package*.json ./
RUN npm install

COPY . .

# 处理行尾符并设置权限
RUN apk add --no-cache dos2unix && \
    dos2unix /app/setup.sh && \
    chmod +x /app/setup.sh && \
    ls -la /app/setup.sh

RUN npm run build

EXPOSE 3000

# 使用绝对路径并明确指定 shell
CMD ["/bin/sh", "/app/setup.sh"]