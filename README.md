# P4E

## 構築方法

### 前準備(初回のみ)

```
/// データベースのデータ保存用ボリューム作成
$ docker volume create --name=p4e-database-data
```

```
/// テスト用データベースの作成
$ p4e-database createdb -U webapp webapp_test
```
