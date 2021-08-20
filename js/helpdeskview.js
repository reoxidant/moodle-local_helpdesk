function togglehistory() {
    let historydiv = document.getElementById("issuehistory");
    let historylink = document.getElementById("togglehistorylink");

    if (historydiv.className === "visiblediv") {
        historydiv.className = "hiddendiv";
        historylink.innerText = showhistory;
    } else {
        historydiv.className = "visiblediv";
        historylink.innerText = hidehistory;
    }
}

const helpdesk_categories = {
    init: function (Y, wwwroot) {
        this.membersCategory = new UpdatableMembersCategory(wwwroot)

    }
}

/**
 * Class UpdatableMembersCombo
 */

function UpdatableMembersCategory(wwwRoot) {
    this.wwwRoot = wwwRoot

    this.connectCallback = {
        success: function (t, o) {
            if (o.responseText !== undefined) {
                let selectEl = document.getElementById("members");
                if (selectEl && o.responseText) {
                    let roles = eval("(" + o.responseText + ")");

                    // Clear the members list box.
                    if (selectEl) {
                        while (selectEl.firstChild) {
                            selectEl.removeChild(selectEl.firstChild)
                        }
                    }

                    // Populate the members list box.
                    for (let i = 0; i < roles.length; i++) {
                        let optgroupEl = document.createElement("optgroup");
                        optgroupEl.setAttribute("label", roles[i].name);

                        for (let j = 0; j < roles[i].users.length; j++) {
                            let optionEl = document.createElement("option");
                            optionEl.setAttribute("value", roles[i].users[j].id);
                            optionEl.title = roles[i].users[j].name;
                            optionEl.innerHTML = roles[i].users[j].name;
                            optgroupEl.appendChild(optionEl);
                        }
                        selectEl.appendChild(optgroupEl);
                    }
                }
            }
            // Remove the loader gif image
            removeLoaderImgs('membersloader', 'memberslabel');
        },

        failure: function () {
            removeLoaderImgs('membersloader', 'memberslabel');
        }
    }
}

UpdatableMembersCategory.prototype.refreshMembers = function () {

    // Get category selector and check selection type
    let selectEl = document.getElementById('categories');
    let selectionCount = 0, categoryId = 0;

    if (selectEl) {
        for (let i = 0; i < selectEl.options.length; i++) {
            selectionCount++;
            if (!categoryId) {
                categoryId = selectEl.options[i].value;
            }
        }
    }

    let singleSelection = selectionCount === 1;

    if (singleSelection) {
        createLoaderImg('membersloader', 'memberslabel');
    }

    // Update the label.

    let spanEl = document.getElementById('thecategory');

    if (singleSelection) {
        spanEl.innerHTML = selectEl.options[selectEl.selectedIndex].title;
    } else {
        spanEl.innerHTML = '&nbsp;';
    }

    // Clear the members list box.
    selectEl = document.getElementById('members');
    if (selectEl) {
        while (selectEl.firstChild) {
            selectEl.removeChild(selectEl.firstChild)
        }
    }

    document.getElementById('showaddmembersform').disabled = !singleSelection;
    document.getElementById('showeditcategorysettingsform').disabled = !singleSelection;
    document.getElementById('deletecategory').disabled = selectionCount === 0;

    if (singleSelection) {

        let sUrl = this.wwwRoot + "/local/helpdesk/view.php?category=" + categoryId + "&action_ajax_getmembersincategory";
        let self = this;
        YUI().use('io', function (Y) {
            Y.io(sUrl, {
                method: 'GET',
                context: this,
                on: self.connectCallback
            });
        });
    }
};

let createLoaderImg = function (elClass, parentId) {
    let parentEl = document.getElementById(parentId);
    if (!parentEl) {
        return false;
    }
    if (document.getElementById('loaderImg')) {
        // A loader image already exists.
        return false;
    }

    let loadingImg = document.createElement('img')

    loadingImg.setAttribute('src', M.util.image_url('/i/ajaxloader', 'moodle'))
    loadingImg.setAttribute('class', elClass);
    loadingImg.setAttribute('alt', "Loading");
    loadingImg.setAttribute('id', 'loaderImg');
    parentEl.appendChild(loadingImg)

    return true
}

let removeLoaderImgs = function (elClass, parentId) {
    let parentEl = document.getElementById(parentId)
    if (parentId) {
        let loader = document.getElementById("loaderImg")
        if (loader) {
            parentEl.removeChild(loader)
        }
    }
}

let is_selection_empty = function (selectEl) {
    let selection = false;
    for (let i = 0; i < selectEl.options.length; i++) {
        if (selectEl.options[i].selected) {
            selection = true;
        }
    }
    return !(selection);
}

let init_add_remove_members_page = function () {
    let add = document.getElementById('add');
    let addselect = document.getElementById('addselect');
    add.disabled = is_selection_empty(addselect);

    addselect.addEventListener('change', function () {
        add.disabled = false;
    })

    let remove = document.getElementById('remove');
    let removeselect = document.getElementById('removeselect');
    remove.disabled = is_selection_empty(removeselect);

    removeselect.addEventListener('change', function () {
        remove.disabled = false;
    })
}

let search_members = function (Y, categoryid) {
    let lastsearch = ""
    let querydelay = 0.5;
    let timeoutid = null;

    let removeselect = document.getElementById("removeselect");
    let removeselect_searchtext = document.getElementById("removeselect_searchtext");
    let removeselect_clearbutton = document.getElementById("removeselect_clearbutton");

    let addselect = document.getElementById('addselect');
    let addselect_searchtext = document.getElementById("addselect_searchtext");
    let addselect_clearbutton = document.getElementById("addselect_clearbutton");

    let cancel_timeout = function () {
        if (timeoutid) {
            clearTimeout(timeoutid);
            timeoutid = null;
        }
    }

    let get_search_text = function (searchfield) {
        return searchfield.value.toString().replace(/^ +| +$/, '');
    }

    removeselect_clearbutton.disabled = get_search_text(removeselect_searchtext) === '';
    addselect_clearbutton.disabled = get_search_text(addselect_searchtext) === '';

    removeselect_searchtext.addEventListener('keyup', function () {
        // Trigger an ajax search after a delay.
        cancel_timeout();
        removeselect_clearbutton.disabled = false;

        timeoutid = setTimeout(function () {
            send_query(removeselect, removeselect_searchtext);
        }, querydelay * 1000);
    });

    removeselect_clearbutton.addEventListener('click', function () {
        removeselect_searchtext.value = "";
        removeselect_clearbutton.disabled = true;
        send_query(removeselect, removeselect_searchtext);
    });

    addselect_searchtext.addEventListener('keyup', function () {
        // Trigger an ajax search after a delay.
        cancel_timeout();
        addselect_clearbutton.disabled = false;

        timeoutid = setTimeout(function () {
            send_query(addselect, addselect_searchtext);
        }, querydelay * 1000);
    });

    addselect_clearbutton.addEventListener('click', function () {
        addselect_searchtext.value = "";
        addselect_clearbutton.disabled = true;
        send_query(addselect, addselect_searchtext);
    });

    let send_query = function (select, searchfield) {

        cancel_timeout()

        let value = get_search_text(searchfield);
        searchfield.className = ''
        if (lastsearch === value) {
            return;
        }

        select.style.backgroundImage = 'url("pix/loading.gif")';
        select.style.backgroundPosition = 'center center';
        select.style.backgroundRepeat = 'no-repeat';

        let url = '/local/helpdesk/searchmembers.php';

        const params = new URLSearchParams();
        params.append('search', value);
        params.append('searchid', select.id)
        params.append('categoryid', categoryid)

        fetch(url, {
                method: "POST",
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: params.toString()
            }
        ).then(
            response => response.json()
        ).then(
            data => {
                lastsearch = value;
                select.style.backgroundImage = '';
                output_options(select, data);
            }
        ).catch(
            error => {
                searchfield.classList.add('error');
                searchfield.style.border = '1px solid red';
                console.log('Request failed', error);
            }
        );
    }

    let output_options = function (select, data) {
        // Clear out the existing options, keeping any ones that are already selected.
        let selectedusers = {};

        let optgroup = select.getElementsByTagName('optgroup')[0];

        for (let i = 0; i < select.options.length; i++) {

            let opt = select.options[i]
            if (opt.selected) {
                selectedusers[opt.value] = {
                    id: opt.value,
                    name: opt.innerText || opt.textContent,
                    disabled: opt.disabled
                }
            }
            opt.remove();
        }
        optgroup.remove()

        output_category(select, data.results, selectedusers);
    }

    let output_category = function (select, users, selectedusers) {
        let optgroup = document.createElement('optgroup');
        let count = 0;
        for (let key in users) {
            if (users.hasOwnProperty(key)) {

                let option = document.createElement('option');

                if (users[key] === false) {
                    option.innerText = "Нет пользователей, соответствующих шаблону поиска";
                    option.disabled = true;
                    optgroup.append(option);
                    break;
                }

                let {id, firstname, lastname, email} = users[key];

                option.value = id;
                option.innerText = firstname + " " + lastname + " (" + email + ")";

                if (selectedusers === true || selectedusers[id]) {
                    option.selected = true;
                    delete selectedusers[id];
                } else {
                    option.selected = false;
                }
                optgroup.appendChild(option);
                count++;
            }
        }

        optgroup.label = "Пользователи (" + count + ")"

        select.appendChild(optgroup)
    }
}