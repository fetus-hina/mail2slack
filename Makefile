all: vendor config/slack.php

clean:
	rm -rf composer.phar vendor

vendor: composer.lock composer.phar
	./composer.phar install -vvv --prefer-dist
	touch -r composer.lock vendor

composer.lock: composer.json composer.phar
	./composer.phar update -vvv

composer.phar:
	curl -sS https://getcomposer.org/installer | php

config/slack.php:
	cp config/slack.sample.php config/slack.php
