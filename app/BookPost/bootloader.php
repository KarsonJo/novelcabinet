<?php

namespace KarsonJo\BookPost {

    use KarsonJo\BookPost\BookMeta\MetaManager;
    use KarsonJo\BookPost\Route\APIRoute;
    use KarsonJo\BookPost\Route\QueryData;

    APIRoute::init();
    QueryData::init();
    MetaManager::init();
}
