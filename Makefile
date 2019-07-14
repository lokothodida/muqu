phpunit:
	vendor/bin/phpunit test --testdox

phpstan:
	vendor/bin/phpstan analyse src test --level=7

tests: phpstan phpunit
