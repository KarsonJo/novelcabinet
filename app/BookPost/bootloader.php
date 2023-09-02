<?php

namespace KarsonJo\BookPost {

    use KarsonJo\BookPost\BookMeta\MetaManager;
    use KarsonJo\BookPost\Route\APIRoute;
    use KarsonJo\BookPost\Route\BookCoverRestResource;
    use KarsonJo\BookPost\Route\BookRestResource;
    use KarsonJo\BookPost\Route\QueryData;
    use KarsonJo\BookPost\Route\RootRestResource;
    use KarsonJo\BookPost\SqlQuery\BookQuery;

    APIRoute::init();

    RootRestResource::createAPI('kbp', 'v1')
        ->addPermission(fn () => current_user_can('import'))
        ->addSubResource(BookRestResource::create('books')
            ->addSubResource(BookCoverRestResource::create('cover')))
        ->init();

    QueryData::init();
    MetaManager::init();
    BookQuery::firstTimeInit();
    BookPost::init();
}
