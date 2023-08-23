import { getContents } from "@scripts/requests/book-post.mjs";

function initContentsButtons() {
    const contentsToggle = document.querySelectorAll(".tag\\.contents-toggle")
    contentsToggle.forEach(el => {
        el.addEventListener("click", async () => {
            try {
                const pid = el.dataset.pid
                const response = await getContents(pid)
                const json = await response.json()
                // console.log(json)
                loadContents(json)
            }
            catch (error) {
                console.log(`Error ${typeof (error)}`, error)
            }
        })
    })
    // bookPost.getContents(17).then(x =>x.json().then(x =>  console.log(x)))

} 

function loadContents(contents) {

}

export function initBookManager() {
    initContentsButtons();
}