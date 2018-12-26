# JDUnionAPIV2

本接口基于https://www.coderdoc.cn/jdapiv2提供的在线API接口修改而来。

可参考内部签名实现，自行再做调整，适配自己的项目。

#### 使用方法

```php
$api = new Unionv2API("key","secret");
$param["skuIds"] = "123345";
//获取推广商品信息接口
$result = $api->queryGoodsPromotionInfo($param);
print_r($result);
```

