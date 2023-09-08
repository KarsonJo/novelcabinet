<?php

namespace KarsonJo\BookPost {

    use KarsonJo\BookPost\BookMeta\MetaManager;
    use KarsonJo\BookPost\Route\APIRoute;
    use KarsonJo\BookPost\Route\BookChapterRestResource;
    use KarsonJo\BookPost\Route\BookCoverRestResource;
    use KarsonJo\BookPost\Route\BookRestResource;
    use KarsonJo\BookPost\Route\GenreRestResource;
    use KarsonJo\BookPost\Route\QueryData;
    use KarsonJo\BookPost\Route\RootRestResource;
    use KarsonJo\BookPost\Route\VolumeChapterRestResource;
    use KarsonJo\BookPost\Route\VolumeRestResource;
    use KarsonJo\BookPost\SqlQuery\BookQuery;

    APIRoute::init();

    RootRestResource::createAPI('kbp', 'v1')
        ->addPermission(fn () => current_user_can('import'))
        ->addSubResource(GenreRestResource::create('genres'))
        ->addSubResource(BookRestResource::create('books')
            ->addSubResource(BookCoverRestResource::create('cover'))
            ->addSubResource(VolumeRestResource::create('volumes')
                ->addSubResource(VolumeChapterRestResource::create('chapters')))
            ->addSubResource(BookChapterRestResource::create('chapters')))
        ->init();

    QueryData::init();
    MetaManager::init();
    BookQuery::firstTimeInit();
    BookPost::init();
}
