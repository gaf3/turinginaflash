
.PHONY: run deploy login

run:
	docker-compose up

deploy:
	scp config/turing.conf root@turinginaflash.com:/etc/nginx/sites-enabled/default
	scp -r www/* root@turinginaflash.com:/var/www/html/
	ssh root@turinginaflash.com "service nginx reload"

login:
	ssh root@turinginaflash.com