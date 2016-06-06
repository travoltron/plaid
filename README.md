# Plaid

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

Laravel5 specific wrapper to interface with [Plaid](https://plaid.com/).

## Install

Via Composer

``` bash
$ composer require travoltron/plaid
```

In `app/config.php` add the following line to the providers array

```bash
Travoltron\Plaid\PlaidServiceProvider::class,
```

Publish configuration file

```bash
php artisan vendor:publish --provider="Travoltron\Plaid\PlaidServiceProvider" --tag="config"
```


## Usage

Add the following line to any file you will be using this library in.

`use Travoltron\Plaid;`


**Auth**

Auth allows you to collect a user's bank account and routing number, along with basic account data and balances. The product performs two crucial functions—it translates bank access credentials (username & password) into an accurate account and routing number. No input of account or routing number is necessary. Secondly it validates that this is the owner of this account number, in a NACHA compliant manner. No need for micro-deposits or any other secondary authentication.

First, add the user:

```php
Plaid::addAuthUser(username, password, pin, type);
```

`username`, `password`, and `type` are mandatory fields, and will return an error if not set. `pin` is ONLY for USAA accounts, otherwise set to null. An up to date list can be found [here](https://plaid.com/docs/api/#institution-overview).

Example request:

```php
$authUser = Plaid::addAuthUser('plaid_test', 'plaid_good', null, 'chase');
```

If the institution does not require MFA (multi-factor authentication), and the configuration for Auth login_only is `false`, you will get this, along with a status code of 200:

```json
{
  "accounts": [
    {
      "_id": "mjj9jp92z2fD1mLlpQYZI1gAd4q4LwTKmBNLz",
      "_item": "aWWVW4VqGqIdaP495QyOSVLN1nzjLwhXaPDJJ",
      "_user": "bkkVkMVwQwfYmBMy9jzqHQob9O1KwpFaEyLOE",
      "balance": {
        "available": 1203.42,
        "current": 1274.93
      },
      "meta": {
        "name": "Plaid Savings",
        "number": "9606"
      },
      "numbers": {
        "routing": "021000021",
        "account": "9900009606",
        "wireRouting": "021000021"
      },
      "institution_type": "fake_institution",
      "type": "depository"
    },
  ...],
  "access_token": "xxxxx"
}
```

Make note of the `access_token`, you will use this to identify this user for all further requests with this account. Laravel's one-to-many relationships from User to PlaidLinks would work great here, but I don't want to impose my designs onto your flow, I'm just sharing what works well for me.

> Note: if you set login_only to false, webhooks will be ignored. If this is the case, you will need to poll periodically for fresh data from Plaid.

If the institution does require MFA, your response will be on a 201 status code. This is very important to watch for: in the case that there are multiple questions needed, you will resubmit the MFA request UNTIL you get a 200.

In this instance, your response will look like this:

```json
{
  "type": "questions",
  "mfa": [{"question":"What was the name of your first pet?"}],
  "access_token": "xxxxx"
}
```

> Note: For question type responses use:
again (for multiple questions), tomato (for final response)
For code-based:	1234
For selections:	tomato (when available), ketchup (when available)

```php
Plaid::authMfa(mfa, plaid_token, method);
```

`mfa` is the supplied answer to the challenge question.
`plaid_token` is the access_token we received in the last request, it is how Plaid knows what user to test the challenge question for.
`method` is only used if the configuration list option is set to true. By default, this is false. Read more about it [here](https://plaid.com/docs/api/#auth-mfa).

Example request:

```php
$authMfa = Plaid::authMfa('1234', 'test_chase');
```

You will repeat this cycle until you get a 200 response code, after which, if login_only is set to false you will get this:

```json
{
  "accounts": [
    {object},
    {object}
  ],
  "access_token": "xxxxx"
}
```

At this point someone is fully set up and ready to go!
To recall auth info whenever, just call:

```php
$getAuthData = Plaid::getAuthData($plaid_token);
```

If someone changes their credentials at their banking institution, you will need to update them within Plaid.

This is exactly the same request as `Plaid::addAuthUser` above, except now you will be passing along the access token.

Example request:

```php
$updateAuthUser = Plaid::updateAuthUser($username, $password, $pin = null, $plaid_token);
```

There may be a requirement for MFA, same as when you added the user initially, so follow those same steps.

To delete a user from Plaid, `Plaid::deleteAuthUser` should be called. This only requires the `plaid_token` to be passed.

Example request:

```php
$deleteAuthUser = Plaid::deleteAuthUser($plaid_token);
```

On success will return this:

```json
{
   "message": "Successfully removed from your account"
}
```

That is the core workflow for all of the Plaid services.
The other services include:

**Connect**

Connect allows developers to receive user-authorized transaction and account data. Data is contained in a set of transaction and account objects, one for each respective entry. Transaction data is standardized across financial institutions, and in most cases transactions are linked to a clean name, entity type, location, and category. Similarly, account data is standardized and returned with a clean name, number, balance, and other meta information where available.

A successful request to Connect looks like this:

```json
"accounts": [
  {
    "_id": "YzzrzBrO9OSzo6BXwAvVuL5dmMKMqkhOoEqeo",
    "_item": "aWWVW4VqGqIdaP495QyOSVLN1nzjLwhXaPDJJ",
    "_user": "bkkVkMVwQwfYmBMy9jzqHQob9O1KwpFaEyLOE",
    "balance": {
      "available": 7205.23,
      "current": 7255.23
    },
    "institution_type": "fake_institution",
    "meta": {
      "name": "Plaid Credit Card",
      "number": "3002"
    },
    "type": "depository",
    "subtype": "checking"
  },
  {
    "_id": "pJPM4LMBNQFrOwp0jqEyTwyxJQrQbgU6kq37k",
    "_item": "aWWVW4VqGqIdaP495QyOSVLN1nzjLwhXaPDJJ",
    "_user": "bkkVkMVwQwfYmBMy9jzqHQob9O1KwpFaEyLOE",
    "balance": {
      "available": 9930,
      "current": 2275.58
    },
    "institution_type": "fake_institution",
    "meta": {
      "limit": 12500,
      "name": "Plaid Credit Card",
      "number": "3002"
    },
    "type": "credit"
  }
...],
"transactions": [
  {
    "_account": "YzzrzBrO9OSzo6BXwAvVuL5dmMKMqkhOoEqeo",
    "_id": "600r0LrVvViXjq96lBpdtyOWboBvzmsaZoeaV",
    "amount": 12.74,
    "date": "2016-03-12",
    "name": "Golden Crepes",
    "meta": {
      "location": {
        "address": "262 W 15th St"
        "city": "New York",
        "state": "NY",
        "zip": "10011",
        "coordinates": {
          "lat": 40.740352,
          "lon": -74.001761
        }
      }
    },
    "pending": false,
    "type": {
      "primary": "place"
    },
    "category": [
      "Food and Drink",
      "Restaurants"
    ],
    "category_id": "13005000",
    "score": {
      "location": {
        "address": 1,
        "city": 1,
        "state": 1
      },
      "name": 0.9
    },
  },
...],
"access_token": "xxxxx"
```

Connect requests also have have additional params that are set in the configuration file: `start_date` and `end_date`, which default to 30 and 0 respectively.

This values, used in conjuction with [Carbon](http://carbon.nesbot.com/) set the date range that Plaid will return data for. It is always in terms of _days ago_, as in return data from 30 days ago to 0 days ago, ie today.

These can be set at request time as well, by passing a date string into the request:

```php
$getConnectData = Plaid::getConnectData($plaid_token, 2015-01-01, 2016-03-15);
```

> Note: The default data from Plaid in their sandbox is dated from 2014 and will not show up with this type of search. Change the `start_date` value from 30 to 999 if you'd like to make sure you see a response.

I have also included two additional methods that get the accounts and transactions separately. They are:

`Plaid::getConnectAccounts($plaid_token)`, this does not require a time window.

`Plaid::getConnectTransactions($plaid_token, $start_date = null, $end_date = null)`, this will only contain transactions for you to use.

**Info**
Info allows you to retrieve various account holder information on file with the financial institution, including names, emails, phone numbers, and addresses.

**Risk**
Risk allows you to retrieve various information pertaining to a user's calculated risk. Risk profiles are generated for each underlying account and include an overall score, a breakdown of the score by various contributing parameters, and the extent of data available for analysis.

Plaid’s risk algorithm detects abnormal behavior by comparing new bank accounts to an internal database. While directly detecting risky behavior is challenging, we use our large dataset to identify normal patterns. Once an account is found to deviate from our definition of normal in our carefully chosen parameter space it is flagged as abnormal.

**Balance**
Balance returns the real-time balance of a user's accounts. It may be used for existing users that were added via any of Plaid's products. The Current Balance is the total amount of funds in the account. The Available Balance is the Current Balance less any outstanding holds or debits that have not yet posted to the account. Note that not all institutions calculate the Available Balance. In the case that Available Balance is unavailable from the institution, Plaid will either return an Available Balance value of null or only return a Current Balance.

Example request:

```php
$balances = Plaid::getBalances($plaid_token);
```

This will return
```json
{
  "accounts": [
    {
      "_id": "mjj9jp92z2fD1mLlpQYZI1gAd4q4LwTKmBNLz",
      "_item": "aWWVW4VqGqIdaP495QyOSVLN1nzjLwhXaPDJJ",
      "_user": "bkkVkMVwQwfYmBMy9jzqHQob9O1KwpFaEyLOE",
      "balance": {
        "available": 1203.42,
        "current": 1274.93
      },
      "meta": {
        "name": "Plaid Savings",
        "number": "9606"
      },
      "institution_type": "fake_institution",
      "type": "depository"
    },
  ...],
  "access_token": "xxxxx"
}
```


**Upgrade**
For an existing user that has been added via any of Plaid's products (Connect, Auth, Income, Info, or Risk), you can upgrade that user to have functionality with other products.

Example request:

```php
$upgradeUser = Plaid::upgrade($plaid_token, $upgrade_to, $webhook = null);
```

`$upgrade_to` must be one of the following `['auth', 'connect', 'income', 'info', 'risk']`.

> Note: You will find it easiest if you take users through the Auth onboarding process first in order to avoid additional MFA requests. Use of a webhook is highly recommended here.

**Search Institutions**
You can search all institutions (including longtail (you HAVE to request access to this from Plaid directly)). It will return the authentication params and in many cases a base64 encoded logo to use.

Example request:

```php
$search = Plaid::search('bank of america');
```

```json
[
  {
    "name": "Bank of America",
    "id": "bofa",
    "logo": "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",
    "fields": [
      {
        "name": "username",
        "label": "Online ID",
        "type": "text"
      },
      {
        "name": "password",
        "label": "Password",
        "type": "password"
      }
    ]
  },
...]
```

`product` options are the same as the viable options for `upgrade_to`, however you most likely will never use this option.
`id` is if you already know the id you're searching for, handy if you want to get a logo or additional data, but still highly unlikely to be used with any frequency.

**Categories**
Most transactions fall under a category. This is extremely useful with targeting spending or isolating patterns for a user. For a full list, click [here](https://github.com/plaid/Support/blob/master/categories.md).

To search categories, you can get all from this:

```php
$getCategories = Plaid::categories($id = null);
```

```json
[
  {
    "id": "10000000",
    "hierarchy": [
      "Bank Fees"
    ],
    "type": "special"
  },
  {
    "id": "10001000",
    "hierarchy": [
      "Bank Fees",
      "Overdraft"
    ],
    "type": "special"
  },
  {
    "id": "10002000",
    "hierarchy": [
      "Bank Fees",
      "ATM"
    ],
    "type": "special"
  },
  {
    "id": "10003000",
    "hierarchy": [
      "Bank Fees",
      "Late Payment"
    ],
    "type": "special"
  },
  {
    "id": "10004000",
    "hierarchy": [
      "Bank Fees",
      "Fraud Dispute"
    ],
    "type": "special"
  },
  {
    "id": "10005000",
    "hierarchy": [
      "Bank Fees",
      "Foreign Transaction"
    ],
    "type": "special"
  },
  {
    "id": "10006000",
    "hierarchy": [
      "Bank Fees",
      "Wire Transfer"
    ],
    "type": "special"
  },
  {
    "id": "10007000",
    "hierarchy": [
      "Bank Fees",
      "Insufficient Funds"
    ],
    "type": "special"
  },
...]
```

Send it empty to retrieve all categories, or send an ID for detailed information.

```php
$getCategoryDetail = Plaid::categories('17001013');
```

```json
{
  "type": "place",
  "hierarchy": [
    "Recreation",
    "Arts & Entertainment",
    "Circuses and Carnivals"
  ],
  "id": "17001013"
}
```


## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.


## Credits

- [travoltron][link-author]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/:vendor/:package_name.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/:vendor/:package_name.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/travoltron/plaid
[link-downloads]: https://packagist.org/packages/travoltron/plaid
[link-author]: https://github.com/travoltron
