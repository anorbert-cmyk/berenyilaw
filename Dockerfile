FROM nginx:alpine

# Nginx konfig template másolás
COPY nginx.conf.template /etc/nginx/templates/default.conf.template

# Statikus fájlok másolása
COPY index.html /usr/share/nginx/html/
COPY dr-berenyi-kristof.png /usr/share/nginx/html/
COPY berenyi-kristof.jpg /usr/share/nginx/html/
COPY csorba-dorottya.jpg /usr/share/nginx/html/

# Expose a Railway-féle dinamikus port
EXPOSE $PORT
