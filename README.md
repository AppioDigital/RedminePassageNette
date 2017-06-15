Quickstart
==========

[![Build Status](https://travis-ci.org/AppioDigital/RedminePassageNette.svg?branch=master)](https://travis-ci.org/AppioDigital/RedminePassageNette)
[![Coverage Status](https://coveralls.io/repos/github/AppioDigital/RedminePassageNette/badge.svg?branch=master)](https://coveralls.io/github/AppioDigital/RedminePassageNette?branch=master)

Installation
------------

The best way to install AppioDigital/RedminePassageNette is using [Composer](http://getcomposer.org/):

```sh
$ composer require appio-digital/redmine-passage-nette
```


## Required classes

This extension need find two implementation of interfaces:
 
 - `Appio\RedmineNette\Security\RedmineResourceProviderInterface`: for get api key resource
 - `Appio\RedmineNette\Security\RedmineResourceKeyInterface`: for get api key

Example class LoggedUserProvider implementing `Appio\RedmineNette\Security\RedmineResourceProviderInterface`


```php
class LoggedUserProvider implements RedmineResourceProviderInterface
{
    /** @var UserRepo */
    private $userRepository;

    /** @var User */
    private $netteUser;

    /**
     * @param UserRepo $userRepository
     * @param User $netteUser
     */
    public function __construct(UserRepo $userRepository, User $netteUser)
    {
        $this->userRepository = $userRepository;
        $this->netteUser = $netteUser;
    }

    /**
     * @return RedmineResourceKeyInterface|null
     */
    public function getResource(): ?RedmineResourceKeyInterface
    {
        return $this->userRepository->find($this->netteUser->getId());
    }
}

```

Example class User implementing `Appio\RedmineNette\Security\RedmineResourceKeyInterface`


```php
class User implements RedmineResourceKeyInterface
{
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true, unique=true)
     */
    private $redmineApiKey;

    /**
     * @return string
     */
    public function getRedmineApiKey(): string
    {
        return $this->redmineApiKey ?? '';
    }
}
```


## Configuration

### Minimal configuration

```yaml
extensions:
    httplug: FreezyBee\Httplug\DI\HttplugExtension
    redmine: Appio\RedmineNette\DI\RedmineExtension


redmine:
    baseUri: 'https://your.redmine.com/api'
```

### Full configuration

```yaml
redmine:
    baseUri: 'https://your.redmine.com/api'
    defaultProjectId: 10
    defaults:

        # default setting - merged to all another
        default:
            assignedToId: 50 # Project manager
            trackerId: 2 # Feature
            statusId: 1 # New
            params:
                some_filter_param: 1
            customFields:
                # some hidden custom field
                3:
                    type: hidden
                    defaultValue: 1

        # defaults for project with id 1
        1:
            assignedToId: 1 # Super project manager
            customFields:
                # text
                4:
                    type: text
                    label: Custom
                    defaultValue: hello
                # checkbox
                15:
                    type: checkbox
                    label: Custom
                    defaultValue: hello

```