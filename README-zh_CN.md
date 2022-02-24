<h1 align="center">Illusionist Searcher</h1>
<div align="center">
基于搜索语法生成数据库查询
<br /><br />

![packagist](https://img.shields.io/packagist/v/illusionist/searcher?style=flat-square)
![php](https://img.shields.io/packagist/php-v/illusionist/searcher?style=flat-square)
![downloads](https://img.shields.io/packagist/dm/illusionist/searcher?style=flat-square)
![license](https://img.shields.io/packagist/l/illusionist/searcher?style=flat-square)
[![Build Status](https://app.travis-ci.com/illusionist-php/searcher.svg?branch=1.0)](https://app.travis-ci.com/illusionist-php/searcher)
<br /><br />
[English](README.md)  | 中文
</div>

## ✨ 特性

- 零配置
- 兼容 [laravel/scout](https://github.com/laravel/scout) 和 [lorisleiva/laravel-search-string](https://github.com/lorisleiva/laravel-search-string)
- 支持字符串和数组两种[搜索语法](#syntax)
- 支持 [laravel](https://github.com/laravel/framework) 框架
- 支持 [thinkphp](https://github.com/top-think/think) 框架
- 支持共体列

## 📦 安装

通过 Composer 安装

```bash
composer require illusionist/searcher
```

## 🔨 Usage

添加 `Searchable` trait 到你的模型

#### Laravel/Lumen

```php
<?php

namnespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;
}
```

#### ThinkPHP

ThinkPHP 版本必须 `>= 5.x`

```php
<?php

namnespace app\model;

use think\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;
}
```

现在，可以使用 [搜索语法](#syntax) 创建数据库查询了

```php
Post::search('title:"Hello world" sort:-created_at,published')->get();
```

## <a id="syntax"></a> 💡 语法

⚠️ 注意：操作符之间的空格对于字符串语法并不重要

### 精确匹配

**字符串语法**

```php
'rating: 0'
'rating = 0'
'title: Hello'               // Strings without spaces do not need quotes
'title: "Hello World"'       // Strings with spaces require quotes
"title: 'Hello World'"       // Single quotes can be used too
'rating = 99.99'
'created_at: "2018-07-06 00:00:00"'
```

**数组语法**

```php
['rating' => 0]
['title' => 'Hello World']
['rating' => 99.99]
['created_at' => '2018-07-06 00:00:00']
```

### 比较

**字符串语法**

```php
'title < B'
'rating > 3'
'created_at >= "2018-07-06 00:00:00"'
```

**数组语法**

```php
['title' => ['<', 'B']]
['rating' => ['>', 3]]
['created_at' => ['>=', '2018-07-06 00:00:00']]
```

### 布尔值

**字符串语法**

```php
'published'         // published = true
'not published'     // published = false
```

**数组语法**

```php
['published']              // published = true
['not' => 'published']    // published = false
```

### 日期值

**字符串语法**

```php
'created_at'                            // created_at is not null
'not created_at'                        // created_at is null

// Year precision
'created_at >= 2020'                    // 2020-01-01 00:00:00 <= created_at
'created_at > 2020'                     // 2020-12-31 23:59:59 < created_at
'created_at = 2020'                     // 2020-01-01 00:00:00 <= created_at <= 2020-12-31 23:59:59
'not created_at = 2020'                 // created_at < 2020-01-01 00:00:00 and created_at > 2020-12-31 23:59:59

// Month precision
'created_at = 01/2020'                  // 2020-01-01 00:00:00 <= created_at <= 2020-01-31 23:59:59
'created_at <= "Jan 2020"'              // created_at <= 2020-01-31 23:59:59
'created_at < 2020-1'                   // created_at < 2020-01-01 00:00:00

// Day precision
'created_at = 2020-12-31'               // 2020-12-31 00:00:00 <= created_at <= 2020-12-31 23:59:59
'created_at >= 12/31/2020"'             // 2020-12-31 23:59:59 <= created_at
'created_at > "Dec 31 2020"'            // 2020-12-31 23:59:59 < created_at

// Hour and minute precisions
'created_at = "2020-12-31 16"'          // 2020-12-31 16:00:00 <= created_at <= 2020-12-31 16:59:59
'created_at = "2020-12-31 16:30"'       // 2020-12-31 16:30:00 <= created_at <= 2020-12-31 16:30:59
'created_at = "Dec 31 2020 5pm"'        // 2020-12-31 17:00:00 <= created_at <= 2020-12-31 17:59:59
'created_at = "Dec 31 2020 5:15pm"'     // 2020-12-31 17:15:00 <= created_at <= 2020-12-31 17:15:59

// Exact precision
'created_at = "2020-12-31 16:30:00"'    // created_at = 2020-12-31 16:30:00
'created_at = "Dec 31 2020 5:15:10pm"'  // created_at = 2020-12-31 17:15:10

// Relative dates
'created_at = today'                    // today between 00:00 and 23:59
'not created_at = today'                // any time before today 00:00 and after today 23:59
'created_at >= tomorrow'                // from tomorrow at 00:00
'created_at <= tomorrow'                // until tomorrow at 23:59
'created_at > tomorrow'                 // from the day after tomorrow at 00:00
'created_at < tomorrow'                 // until today at 23:59
```

**数组语法**

```php
['created_at']                                      // created_at is not null
['not' => 'created_at']                             // created_at is null

// Year precision
['created_at' => ['>=', '2020']]                    // 2020-01-01 00:00:00 <= created_at
['created_at' => ['>', '2020']]                     // 2020-12-31 23:59:59 < created_at
['created_at' => '2020']                            // 2020-01-01 00:00:00 <= created_at <= 2020-12-31 23:59:59
['not' => ['created_at' => '2020']]                 // created_at < 2020-01-01 00:00:00 and created_at > 2020-12-31 23:59:59

// Month precision
['created_at' => '01/2020']                         // 2020-01-01 00:00:00 <= created_at <= 2020-01-31 23:59:59
['created_at' => ['<=', 'Jan 2020']                 // created_at <= 2020-01-31 23:59:59
['created_at' => ['<', '2020-1']]                   // created_at < 2020-01-01 00:00:00

// Day precision
['created_at' => '2020-12-31']                      // 2020-12-31 00:00:00 <= created_at <= 2020-12-31 23:59:59
['created_at' => ['>=', '12/31/2020']               // 2020-12-31 23:59:59 <= created_at
['created_at' => ['>', 'Dec 31 2020']]              // 2020-12-31 23:59:59 < created_at

// Hour and minute precisions
['created_at' => '2020-12-31 16']                   // 2020-12-31 16:00:00 <= created_at <= 2020-12-31 16:59:59
['created_at' => '2020-12-31 16:30']                // 2020-12-31 16:30:00 <= created_at <= 2020-12-31 16:30:59
['created_at' => 'Dec 31 2020 5pm']                 // 2020-12-31 17:00:00 <= created_at <= 2020-12-31 17:59:59
['created_at' => 'Dec 31 2020 5:15pm']              // 2020-12-31 17:15:00 <= created_at <= 2020-12-31 17:15:59

// Exact precision
['created_at' => '2020-12-31 16:30:00']             // created_at = 2020-12-31 16:30:00
['created_at' => 'Dec 31 2020 5:15:10pm']           // created_at = 2020-12-31 17:15:10

// Relative dates
['created_at' => 'today']                           // today between 00:00 and 23:59
['not' => ['created_at' => 'today']]                // any time before today 00:00 and after today 23:59
['created_at' => ['>=', 'tomorrow']]                // from tomorrow at 00:00
['created_at' => ['<=', 'tomorrow']]                // until tomorrow at 23:59
['created_at' => ['>', 'tomorrow']]                 // from the day after tomorrow at 00:00
['created_at' => ['<', 'tomorrow']]                 // until today at 23:59
```

### 列表

**字符串语法**

```php
'status:Finished,Archived'
'status in(Finished,Archived)'
'title in (Hello, Hi, "My super article")'
```

**数组语法**

```php
['status' => ['Finished', 'Archived']]
['status' => ['in', 'Finished', 'Archived']]
['title' => ['in', 'Hello', 'Hi', 'My super article']]
```

### 区间

**字符串语法**

```php
'created_at:2021-1-1~2021-12-31'
'created_at between(2021-1-1, 2021-12-31)'
```

**数组语法**

```php
['created_at' => ['between', ['2021-1-1', '2021-12-31']]]
['created_at' => ['between', '2021-1-1', '2021-12-31']]
```

### 否定

**字符串语法**

```php
'not title:Hello'
'not title="My super article"'
'not rating:0'
'not rating>4'
'not status in (Finished,Archived)'
'not published'                         // published = false
'not created_at'                        // created_at is null
```

**数组语法**

```php
['not' => ['title' => 'Hello']]
['not' => ['rating' => 0]]
['not' => ['rating' => ['>', 4]]]
['not' => ['status' => ['in', 'Finished', 'Archived']]]
['not' => ['published']]                                   // published = false
['not' => ['created_at']]                                  // created_at is null
```

### 空值

**字符串语法**

`NULL` 不区分大小写

```php
'body:NULL'         // body is null
'not body:null'     // body is not null
```

**数组语法**

```php
['body' => null]               // body is null
['not' => ['body' => null]]    // body is not null
```

### 搜索查询

⚠️ 术语不能设置成布尔或日期型，否则将当作布尔或日期值处理

**字符串语法**

```php
'Apple'             // %Apple% like at least one of the searchable columns
'"John Doe"'        // %John Doe% like at least one of the searchable columns
'not "John Doe"'    // %John Doe% not like any of the searchable columns
```

**数组语法**

```php
['Apple']                  // %Apple% like at least one of the searchable columns
['not' => 'John Doe']      // %John Doe% not like any of the searchable columns
```

### 与/或嵌套查询

**字符串语法**

```php
'title:Hello body:World'        // 隐式 and
'title:Hello and body:World'    // 显示 and
'title:Hello or body:World'     // 显示 or
'A B or C D'                    // 等同于 '(A and B) or (C and D)'
'A or B and C or D'             // 等同于 'A or (B and C) or D'
'(A or B) and (C or D)'         // 显式嵌套优先级
'not (A and B)'                 // 等同于 'not A or not B'
'not (A or B)'                  // 等同于 'not A and not B'
```

**数组语法**

Keyword use `studly-caps` format, e.g. `andOr` can be written as `and_or` or `and-or` or `and or` or `AndOr`;

```php
['title' => 'Hello', 'body' => 'World']                // 隐式 and
['and' => ['title' => 'Hello', 'body' => 'World']]     // 显示 and
['or' => ['title' => 'Hello', 'body' => 'World']]      // 显示 or
['or' => [['A', 'B'], ['C', 'D']]]                     // 等同于 '(A and B) or (C and D)'
['or' => ['A', ['B', 'C'], 'D']]                       // 等同于 'A or (B and C) or D'
['andOr' => [['A', 'B'], ['C', 'D']]]                  // 等同于 '(A or B) and (C or D)'
['not' => ['A', 'B']]                                  // 等同于 'not A or not B'
['notOr' => ['A', 'B']]                                // 等同于 'not A and not B'
```

### 关联

**字符串语法**

```php
// 简单 has 检查
'comments'                              // Has comments
'not comments'                          // Doesn't have comments
'comments = 3'                          // Has 3 comments
'not comments = 3'                      // Doesn't have 3 comments
'comments > 10'                         // Has more than 10 comments
'not comments <= 10'                    // Same as before
'comments <= 5'                         // Has 5 or less comments
'not comments > 5'                      // Same as before

// "WhereHas" 检查
'comments: (title: Superbe)'            // 具有 title 为 "Superbe" 的 comments
'comments: (not title: Superbe)'        // 具有 title 不为 "Superbe" 的 comments
'not comments: (title: Superbe)'        // 具有 title 不为 "Superbe" 的 comments
'comments: (quality)'                   // Has comments whose searchable columns match "%quality%"
'not comments: (spam)'                  // Doesn't have comments marked as spam
'comments: (spam) >= 3'                 // Has at least 3 spam comments
'not comments: (spam) >= 3'             // Has at most 2 spam comments
'comments: (not spam) >= 3'             // Has at least 3 comments that are not spam
'comments: (likes < 5)'                 // Has comments with less than 5 likes
'comments: (likes < 5) <= 10'           // Has at most 10 comments with less than 5 likes
'not comments: (likes < 5)'             // Doesn't have comments with less than 5 likes
'comments: (likes > 10 and not spam)'   // Has non-spam comments with more than 10 likes

// "WhereHas" shortcuts
'comments.title: Superbe'               // Same as 'comments: (title: Superbe)'
'not comments.title: Superbe'           // Same as 'not comments: (title: Superbe)'
'comments.spam'                         // Same as 'comments: (spam)'
'not comments.spam'                     // Same as 'not comments: (spam)'
'comments.likes < 5'                    // Same as 'comments: (likes < 5)'
'not comments.likes < 5'                // Same as 'not comments: (likes < 5)'

// Nested relationships
'comments: (author: (name: John))'      // Has comments from the author named John
'comments.author: (name: John)'         // Same as before
'comments.author.name: John'            // Same as before

// Nested relationships are optimised
'comments.author.name: John and comments.author.age > 21'   // Same as: 'comments: (author: (name: John and age > 21))
'comments.likes > 10 or comments.author.age > 21'           // Same as: 'comments: (likes > 10 or author: (age > 21))
```

**数组语法**

```php
// Simple "has" check
['comments']                                               // Has comments
['not' => ['comments']]                                    // Doesn't have comments
['comments' => 3]                                          // Has 3 comments
['not' => ['comments' => 3]]                               // Doesn't have 3 comments
['comments' => ['>', 10]]                                  // Has more than 10 comments
['not' => ['comments' => ['<=', 10]]]                      // Same as before
['comments' => ['<=', 5]]                                  // Has 5 or less comments
['not' => ['comments' => ['>', 5]]]                        // Same as before

// "WhereHas" check
['comments' => ['title' => 'Superbe']]                     // Has comments with the title "Superbe"
['comments' => ['not' => ['title' => 'Superbe']]]          // Has comments whose titles are different than "Superbe"        
['not' => ['comments' => ['title' => 'Superbe']]]          // Doesn't have comments with the title "Superbe"
['comments' => 'quality']                                  // Has comments whose searchable columns match "%quality%"
['not' => ['comments' => 'spam']]                          // Doesn't have comments marked as spam
['comments' => ['spam', ['>=', 3]]]                        // Has at least 3 spam comments
['not' => ['comments' => ['spam', ['>=', 3]]]]             // Has at most 2 spam comments
['comments' => ['not' => 'spam', ['>=', 3]]]               // Has at least 3 comments that are not spam
['comments' => ['likes' => ['<', 5]]]                      // Has comments with less than 5 likes
['comments' => ['likes' => ['<', 5], ['<=', 10]]]          // Has at most 10 comments with less than 5 likes
['not' => ['comments' => ['likes' => ['<', 5]]]]           // Doesn't have comments with less than 5 likes
['comments' => ['likes' => ['<', 5], 'not' => 'spam']]     // Has non-spam comments with more than 10 likes

// Nested relationships
['comments' => ['author' => ['name' => 'John']]]           // Has comments from the author named John
```

## ⚔️ 进阶

### 短语搜索

如果一个搜索列不是布尔或日期列，就会调用 `getQueryPhraseColumns` 函数来获取列名，如果在返回值中没有指定操作符，默认为 `like`

返回多个列时将共享一个值，并以 `或` 的形式组装查询条件

**示例:**

```php
<?php

namnespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;

    /**
     * 获取查询短语的列
     *
     * @param  string  $phrase
     * @return array
     */
    public function getQueryPhraseColumns($phrase)
    {
        if (is_numeric($phrase)) {
            return ['stars' => '>=', 'comments.stars' => '>='];
        }

        return ['title'];
    }
}

'lonely' // 等同于：
$query->where('title', '%lonely%');

'3000' // 等同于：
$query->where(function ($query) {
    $query->where('stars', '>=', '3000', 'or')
        ->whereHas('comments', function ($query) {
            $query->where('stars', '>=', '3000')
        });
});
```

### 关联查询

如果定义了一个关联方法并且它是可搜索的列，就可以执行关联查询。例如：关联统计、关联加载、基于关联是否存在的查询等

```php
<?php

namnespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}

// 查询关联是否存在  (会自动处理关系的本地键以及外键)
'comments'                          // $query->has('comments');

// 关联统计
'select:comments_count'            // $query->withCount('comments');

// 关联加载 (会自动处理关系的本地键以及外键)
'select:comments'                  // $query->select('id')->with('comments');
'select:comments.title'            // $query->select('id')->with('comments:id,title')
```

### 配置可搜索的列

多条件查询时可以设置 `searchable` 属性，因为针对多条件查询时设置了查询保护，当用户通过 HTTP 请求传入了非预期的参数，可以通过该属性过滤掉非预期参数以此来防止超权获取数据。

默认值是 `模型表的真实列` 以及 `关联方法名`

```php
<?php

namnespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;

    protected $searchable = ['author', 'created_at'];
}

'author:kayson title:hello'  // 等同于：
$query->where('author', '=', 'kayson');
```

### 配置布尔和日期列

#### Laravel/Lumen

使用 `casts` 属性指定布尔和日期列

```php
<?php

namnespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;

    protected $casts = [
        'published' => 'boolean',
        'created_at' => 'datetime',
    ];
}
```

#### ThinkPHP

使用 `type` 属性指定布尔和日期列

```php
<?php

namnespace app\model;

use think\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;

    protected $type = [
        'published' => 'boolean',
        'created_at' => 'datetime',
    ];
}
```

### 配置关键字

通过重写 `getRelaSearchName` 函数来实现自定义关键字以及共体列配置。

⚠️ `selec`, `order_by`, `offset` 是保留关键字，请不要跟查询列冲突

```php
<?php

namnespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illusionist\Searcher\Contracts\Searchable as SearchableContract;
use Illusionist\Searcher\Eloquent\Searchable;

class Post extends Model implements SearchableContract
{
    use Searchable;

    /**
     * Get the real name of the given search column.
     *
     * @param  string  $key
     * @return string|array
     */
    public function getRelaSearchName($key)
    {
       switch ($key) {
            case 'field':
                return 'select';
            case 'sort':
                return 'order_by';
            case 'from':
                return 'offset';
            case 'stars':
                return ['stars', 'comments.stars'];
            default:
                return $key;
        }
    }
}

'field:id,name' // 等同于：
$query->select(['id', 'name']);

'stars:3000' // 等同于：
$query->where(function ($query) {
    $query->where('stars', '>=', '3000', 'or')
        ->whereHas('comments', function ($query) {
            $query->where('stars', '>=', '3000')
        });
});
```
