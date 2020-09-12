<?php
$config = array (	
		//应用ID,您的APPID。
		'app_id' => "2021000116698563",

		//商户私钥
		'merchant_private_key' => "MIIEowIBAAKCAQEArlVT3Kxt/E+2IQGO6IELN8D9arNsJLtLVAuT3PvaGIrREPpL7Zfqh3nylqo7EtxAr8DZ9JN5DNq4fQR8QFNr6ARNfJ22lqsjsCT6QYudX9SJUUHTnEqE/DaehKtnH9+YubqwKQdF1cjHOHpQKV6pm4RhvJAt5HVQTEm6dWVLA/LUqaoksj0XP8iVNAOWhTQlYtaSeou86M0ERLQPgHDkHYomA/AkGkcxNrpvae3nGj4LKcrLO2vUkfLaRWrSF8FTnXRd65qpGtYZRQopWKh02f2gTaDv/ds9qQWtUcgdA/EpxXC7ryJIz4oZaJBRK4ikEweOBDiUBSlHBjzEasNRIQIDAQABAoIBAC5x5SnBdnvl8KvScnRXDNoJvHoU2xbeHy+A9h/FqVEoipJvXJMsCfuV7Z9eMubPbxxj0K1sLK/UBZqo+0FX+EOUAeDVU7PIXHWtJE8aFw9U3FCdrPbSk6NuGcVWtN60/0dcCVqk6WIPX6QYUgrzgVRBbJiEAPYAmyf37IkiN14RfywtAOfw5L8IZ0eFHnbw5/6CR/5yDa5qBF67CdbUdtESIXRY8taQ9OaaJns6F3/nU3U5pWpT5Ol/5uMNlTbiteC6iS2JFR4Lnc2Z++No+9fyWomOgCwJEHUT1oMJzptY18qSrwepKrT0VqDfoKf7r09WKae52isQcVel3eMxJdECgYEA1a7C29ZDF4vjC+rntZaK5wNi1aEfqWJzkcrcp3yTgXNTk9dvJ9gXZCRpUsmKACRAEAuITy8jNnUQP1NCihoFGpJTUbaNFoaXYwpiO/5pD9WWofR1vwCe8w1cpdwbgSIKbiqqjncuCO6Qmwm3/za7PyeNfedOzOW5rEzkQR4ayh8CgYEA0NukLM/AVmLT6GoQPLdOabj5iVhsqTBCEHau3zhDO8IvuxL04u34gOTR1Dv503Xc2p5y464TQj/E3Wjp0RqmZPqzlPlf4D4FaiT1jkNcq8rHBepO4gLoAa1AHQAFYN2CwAMDu+AnlzvVuWleXikrmARSdfUZI0325nAMA1Aw/L8CgYBVoIBpQ8UC7IWBQxKzC66BwDOc08IySEdzhs1BOISLfUFinxPl7YwCDy09hZGNWPlq9JQc1xDPQ73NAnpJiP++lCBbQEHtYuXLRF/1Fo/XToncQG/mqEMJtrMHq9pBtvhjCSnriQqjfaXo2s4msTH7rsfeShCjvvLWfsGA7qVhlQKBgFdR/SnfEgML/pzE+1TnLpl1BJ8voP57dqN7e1FILJPndB06p7fY1nTNNg0Npj2KoXOlm1MljFe+YYhQPLF3+ultB3fZawRN6eWe3itEbpJEjUEj3ScRH/7TeGKnh5ggBHJS0FTUOrk4Avsp2pJzlA5SgEdg2cmw5vEhF2SrOk/xAoGBAIGKs3Z2QslXopz9yVkX3jVdrz44X6BbVX5OuQr/yOnp2iqAk/EcoRKwEd4i1uDAPR/Kzvy356RaWeXBovy6i6LJVzLO+RoHojVlSYy+FOIeqxx9jQT0sZIGoQ/PkNafKhWK0NKspe0N+mOUckBLRTTyEGrZsE62610t0HT0vSwG",
		
		//异步通知地址        外网可访问网关地址
		'notify_url' => "http://www.pyg.com/home/order/notify",
		
		//同步跳转
		'return_url' => "http://www.pyg.com/home/order/callback",

		//编码格式
		'charset' => "UTF-8",

		//签名方式
		'sign_type'=>"RSA2",

		//支付宝网关
//		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
        'gatewayUrl' => "https://openapi.alipaydev.com/gateway.do",



		//支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
		'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAoF3vXaOh1qUDl/uImPN1mWpLjRKtJAzgd9PLz1jaLkN5g6kRVCw9EOg4EUzOhN+i1aP/CVnYd3HinlX0lDW2WVyJ3pxPvdVoSdPvO38A0zrvIpSWXv0YAD4ebbv++bqIjShCCQ8kjWWaVteBKVOlBLl9VPVaFmCjhB1phNWHDGy5LlaoBEzpWcB2SGotoISz82jVMNxt8F2XkrKgVMzkpb5A10kZKYKkhN2wJ2DzD4GJ/R+66j/DzSCOmMKj9ANr0jUUKkPwBZwC3e1OTemA856mo4G27BisQ/ZCrbazxTtJj5ogaC6UWkHkzXbGiyZu9AstIra1knYbmyfENy75dQIDAQAB",
);