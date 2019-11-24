# みんなのPHP 5章 サンプル

[みんなのPHP](https://gihyo.jp/book/2019/978-4-297-11055-0) 5章 CI/CDのサンプルです。

## 構築方法

### 前準備(初回のみ)

```
/// データベースのデータ保存用ボリューム作成
$ docker volume create --name=p4e-database-data
```

## 起動方法

```
$ docker-compose up -d
```

### テストの実行

#### 前準備(初回のみ)

```
/// テスト用データベースの作成
$ docker exec -i p4e-database createdb -U webapp webapp_test
/// composer install
$ docker exec -i p4e-app composer install --dev
/// データベースのマイグレーション
$ docker exec -i p4e-app /bin/bash -c "./bin/cake migrations migrate"
```

#### テストの実行

```
$ docker exec -i p4e-app /srv/cms/vendor/bin/phpunit
```

## 本番用ビルド

### buiuld

```
$ docker build -t p4e-example-app . --build-arg HTPASSWD="p4e:ハッシュ化したパスワード文字列" 
```

### run

```
$ docker run -it --rm -d -p 80:80 -e APP_ENV=production --name p4e-example-app p4e-example-app:latest
```
