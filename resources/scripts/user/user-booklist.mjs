import Muuri from 'muuri';
import { deletePost, getContents, renamePost, trashPost, untrashPost } from "@scripts/requests/book-post.mjs";
import * as bookPost from "@scripts/requests/book-post.mjs";
import { showAlert } from '@scripts/alert.mjs';
import { doAndNotify } from '@scripts/sitemain.mjs';

let volumeGrid;
let chapterGrids;

function getBookLoadingIndicator(el) {
    return (el ?? document).querySelector(".tag-book-loading");
}

function getContentsLoadingIndicator(el) {
    return (el ?? document).querySelector(".tag-contents-loading");
}

function getContentsContainer(el) {
    return (el ?? document).querySelector(".tag\\.draggable-contents");
}

function getVolumesContainer(el) {
    return (el ?? document).querySelector(".tag-draggable-volume-grid");
}

function getChaptersContainer(el) {
    return (el ?? document).querySelector(".tag-draggable-chapter-grid");
}

function getVolumeItemTemplate(el) {
    return (el ?? document).querySelector("#draggable-volume-item-tpl");
}

function getChapterItemTemplate(el) {
    return (el ?? document).querySelector("#draggable-chapter-item-tpl");
}

function getVolumeToggleInput(el) {
    return (el ?? document).querySelector(".tag-volume-toggle");
}

function getVolumeToggleLabel(el) {
    return (el ?? document).querySelector(".tag\\.volume-label");
}

function getBookList(el) {
    return (el ?? document).querySelector(".tag-book-list");
}

const classDeletebtn = "tag-trash-btn"
// function getTrashButton(el) {
//     return (el ?? document).querySelector("." + classDeletebtn);
// }

// function getContentsButton(el) {
//     return (el ?? document).querySelector(".tag-contents-btn");
// }

// function getRenameButton(el) {
//     return (el ?? document).querySelector(".tag\\.rename-btn");
// }

function initBookListItems() {


    // fillEditPostUrl(volumeItem.querySelector(".tag-edit-btn"), vid);

    /**
     * document事件委托
     * 负责book list相关操作
     */
    document.addEventListener("click", async (event) => {
        const target = event.target;

        // contents toggle
        if (target.closest(".tag-contents-btn")) {
            fetchContents(getClosestPostId(target));
        }

        // trash button for book or volume/chapter
        else if (target.closest(".tag-trash-btn")) {
            showAlert("info", null, "delete request sent");
            event.preventDefault();
            await doAndNotify(trashPost(getClosestPostId(target)));

            updateBookInfo(target);
            // // in contents: reload contents
            // if (target.closest(".tag\\.draggable-contents"))
            //     fetchContents(getClosestPostId(target));
            // // in book list: reload booklist
            // else if (target.closest(".tag-book-list"))
            //     refetchBookList();
        }

        // untrash button
        else if (target.closest(".tag-untrash-btn")) {
            showAlert("info", null, "untrash request sent");
            event.preventDefault();
            await doAndNotify(untrashPost(getClosestPostId(target)));

            updateBookInfo(target);
        }

        // permanantly delete
        else if (target.closest(".tag-delete-btn")) {
            showAlert("info", null, "delete request sent");
            event.preventDefault();
            await doAndNotify(deletePost(getClosestPostId(target)));

            updateBookInfo(target);
        }
        // tab switching

    });

    /**
     * 负责切换book filter tab
     */
    const filterTab = document.querySelector(".tag-filter-tabs");
    filterTab?.addEventListener("click", event => {
        const target = event.target;
        const activeButton = target.closest("a");
        if (activeButton) {
            for (const button of filterTab.querySelectorAll("a"))
                button.classList.remove("selected");

            activeButton.classList.add("selected");
            refetchBookList(activeButton.href);
            // 更改路径
            history.pushState("", "", activeButton.href);
            event.preventDefault();
        }
    })

    /**
     * 负责提交contents order的改变
     */
    document.querySelector(".tag-contents-submit-btn").addEventListener("click", updateContents);


    function getClosestPostId(el) {
        return el.closest("[data-post-id]").dataset.postId;
    }

    function updateBookInfo(target) {
        console.log("refetch");
        if (target.closest(".tag\\.draggable-contents"))
            fetchContents(getClosestPostId(target));
        // in book list: reload booklist
        else if (target.closest(".tag-book-list"))
            refetchBookList();
    }
}

/**
 * 请求指定的html，取出其中的book-list并替换
 * 需要确保给定的html与目前页面形式一致
 * @param {string} href 超链接，为空则默认当前链接
 */
async function refetchBookList(href = undefined) {

    if (!href)
        href = window.location.href;

    const booklist = getBookList();

    try {
        // 改变视觉
        booklist.innerText = '';
        setBookLoading(true);
        // 请求
        const response = await fetch(href, {
            method: 'GET',
        })

        const html = await response.text();
        const parser = new DOMParser();

        const doc = parser.parseFromString(html, 'text/html');
        booklist.replaceWith(getBookList(doc));
    }
    catch (exception) {
        booklist.innerText = '=== ERROR ===';
        console.log(exception);
    }
    finally {
        setBookLoading(false);
    }

    function setBookLoading(visible) {
        const el = getBookLoadingIndicator();
        el.style.display = visible ? "block" : ""

    }
}


// ===================== contents =====================

async function fetchContents(bid) {

    const volumesContainer = getVolumesContainer();
    try {

        // 改变视觉
        volumesContainer.replaceChildren();
        volumesContainer.style.height = "auto";
        setContentsLoading(true);


        // 请求
        const response = await getContents(bid)
        const json = await response.json()
        console.log(json)
        loadContents(json)
    }
    catch (error) {
        console.log(`Error ${typeof (error)}`, error)
    }
    finally {
        setContentsLoading(false);
    }

    function setContentsLoading(visible) {
        const el = getContentsLoadingIndicator();
        el.style.display = visible ? "block" : "";
    }
}



function loadContents(jsonData) {
    // get container
    const contents = getContentsContainer();
    const volumesContainer = getVolumesContainer(contents);
    const volumeTemplate = getVolumeItemTemplate(contents);
    const chapterTemplate = getChapterItemTemplate(contents);

    contents.dataset.postId = jsonData.id;

    // clear old
    volumesContainer.replaceChildren();

    // create volume
    for (let volume of jsonData.volumes) {
        const vid = volume.id;
        const volumeItem = volumeTemplate.content.cloneNode(true);
        const chapterContainer = getChaptersContainer(volumeItem);

        // setup volume
        volumeItem.children[0].dataset.postId = vid;
        volumeItem.querySelector(".tag-volume-title").innerText = volume.title;
        volumeItem.querySelector(".tag-volume-url").href = volume.url;
        fillEditPostUrl(volumeItem.querySelector(".tag-edit-btn"), vid);

        // the toggle
        const volumeToggle = getVolumeToggleInput(volumeItem);
        const volumeLabel = getVolumeToggleLabel(volumeItem);
        volumeToggle.id = `volume-${vid}`;
        volumeLabel.htmlFor = volumeToggle.id;

        // create chapter
        for (let chapter of volume.chapters) {
            const cid = chapter.id;
            const chapterItem = chapterTemplate.content.cloneNode(true);

            // setup chapter
            chapterItem.children[0].dataset.postId = cid;
            chapterItem.querySelector(".tag-chapter-title").innerText = chapter.title;
            chapterItem.querySelector(".tag-chapter-url").href = chapter.url;
            fillEditPostUrl(chapterItem.querySelector(".tag-edit-btn"), cid);


            chapterContainer.appendChild(chapterItem);
        }

        volumesContainer.appendChild(volumeItem);

    }
    // init dragging
    initContentsDragging(contents);
}

function fillEditPostUrl(a, id) {
    let editUrl = new URL(a.href);
    editUrl.searchParams.set("post", id);
    a.href = editUrl.toString();
}


function initContentsDragging(contents) {
    const dragContainer = contents;
    const volumesContainer = getVolumesContainer(dragContainer);
    // const volumeGrid = 
    chapterGrids = [];
    volumeGrid;

    dragContainer.querySelectorAll(".tag-draggable-chapter-grid").forEach(el => {
        let grid = new Muuri(el, {
            dragEnabled: true,
            dragContainer: dragContainer,
            dragSort: () => chapterGrids,
            dragHandle: ".tag\\.grip-chapter",
            dragAutoScroll: {
                targets: () => {
                    return [
                        // { element: window, priority: 0 },
                        { element: dragContainer, priority: 1 },
                    ];
                }
            },

        });
        grid.on('dragInit', freeze);
        grid.on('dragReleaseEnd', unfreeze);
        grid.on('layoutStart', updateVolumnGridLayout);
        chapterGrids.push(grid);
    });
    // console.log(chapterGrids);

    volumeGrid = new Muuri(volumesContainer, {
        dragEnabled: true,
        dragContainer: dragContainer,
        // dragSort: () => volumeGrids,
        dragHandle: ".tag\\.grip-volume",
        dragAutoScroll: {
            targets: () => {
                return [
                    // { element: window, priority: 0 },
                    { element: dragContainer, priority: 1 },
                ];
            }
        },
    })
    volumeGrid.on("dragInit", (item) => {
        draggingUpdatLayout(true);
        freeze(item);
    });
    volumeGrid.on("dragReleaseEnd", (item) => {
        unfreeze(item);
        draggingUpdatLayout(false);
    });


    volumesContainer.addEventListener("change", (event) => {
        if (event.target.classList.contains("tag-volume-toggle"))
            updateVolumnGridLayout();
    })



    function draggingUpdatLayout(start) {
        if (start)
            dragContainer.classList.add("dragging");
        else
            dragContainer.classList.remove("dragging");
        updateVolumnGridLayout();
    }

    function updateVolumnGridLayout() {
        if (volumeGrid)
            volumeGrid.refreshItems().layout();
    }

    function freeze(item) {
        item.getElement().style.width = item.getWidth() + 'px';
        item.getElement().style.height = item.getHeight() + 'px';
    }

    function unfreeze(item) {
        item.getElement().style.width = '';
        item.getElement().style.height = '';
        item.getGrid().refreshItems([item]);
    }
}

async function updateContents() {
    let bookHierarchy = {};
    const book = getContentsContainer();
    if (!book || !book.dataset.postId)
        return;

    const bookId = book.dataset.postId;
    console.log(bookId);
    bookHierarchy["id"] = bookId;
    bookHierarchy["volumes"] = [];

    console.log(volumeGrid.getElement());
    console.log(volumeGrid.getItems().map(x => x.getElement()));
    console.log(chapterGrids[0].getElement());
    console.log(chapterGrids[0].getItems().map(x => x.getElement()));
    // console.log(volumeGrid.getItems()[0]);
    // console.log(volumeGrid.getItems());
    // console.log(volumeGrid.getItems(0)[0]);

    const volumes = volumeGrid.getItems().map(x => x.getElement());

    // const volumes = book.querySelectorAll(".tag-draggable-volume-grid>.muuri-item");
    // console.log(volumes);

    if (!volumes)
        return;


    let volumeHierarchyDict = new Map();
    for (const volume of volumes) {
        const vid = volume.closest("[data-post-id]").dataset.postId;

        volumeHierarchyDict.set(vid, {
            id: vid,
            chapters: []
        });
    }

    for (const chapterGrid of chapterGrids) {
        const chapters = chapterGrid.getItems().map(x => x.getElement());
        const vid = chapterGrid.getElement().closest("[data-post-id]").dataset.postId;
        for (const chapter of chapters) {
            volumeHierarchyDict.get(vid)["chapters"].push({ "id": chapter.dataset.postId });
        }
    }

    bookHierarchy["volumes"] = [...volumeHierarchyDict.values()];

    // for (const volume of volumes) {
    //     // console.log(volume);
    //     if (!volume.dataset.postId)
    //         continue;

    //     // console.log(123);
    //     let volumeHierarchy = {};
    //     volumeHierarchy["id"] = volume.dataset.postId;
    //     volumeHierarchy["chapters"] = [];
    //     const chapters = volume.querySelectorAll(".tag-draggable-chapter-grid>.muuri-item");

    //     // console.log(chapters);
    //     if (chapters)
    //         for (const chapter of chapters)
    //             if (chapter.dataset.postId)
    //                 volumeHierarchy["chapters"].push({ "id": chapter.dataset.postId });

    //     bookHierarchy["volumes"].push(volumeHierarchy);
    // }

    if (bookHierarchy["volumes"].length === 0)
        return;

    console.log(bookHierarchy);

    showAlert("info", null, "update contents request sent");
    await doAndNotify(bookPost.updateContents(bookId, bookHierarchy));
}









export function initBookManager() {
    // initContentsButtons();
    // initContentsDragging();
    initBookListItems();
    // console.log("initBookManager");

}