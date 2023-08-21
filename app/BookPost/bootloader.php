<?php

namespace KarsonJo\BookPost {

    use KarsonJo\BookPost\BookMeta\MetaManager;
    use KarsonJo\BookPost\Route\APIRoute;
    use KarsonJo\BookPost\Route\QueryData;
    use KarsonJo\BookPost\SqlQuery\BookQuery;

    APIRoute::init();
    QueryData::init();
    MetaManager::init();
    BookQuery::firstTimeInit();
    BookPost::init();
}
