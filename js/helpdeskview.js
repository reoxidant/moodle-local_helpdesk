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

// Updates the current groups information shown about a user when a user is selected.

function updateUserSummary() {
    let selectEl = document.getElementById('addselect'),
        summaryDiv = document.getElementById('category-usersummary'),
        length = selectEl.length,
        selectCnt = 0,
        selectIdx = -1,
        i;

    for (i = 0; i < length; i++){
        if(selectEl.options[i].selected) {
            selectCnt++;
            selectIdx = i;
        }
    }

    if(selectCnt === 1 && userSummaries[selectIdx]) {
        summaryDiv.innerHTML = userSummaries[selectIdx];
    } else {
        summaryDiv.innerHTML = '';
    }

    return true
}

// MARK: Need to testing

function init_add_remove_members_page(Y) {
    let add = Y.one('#add');
    let addselect = M.core_user.get_user_selector('addselect');
    add.set('disabled', addselect.is_selection_empty());
    addselect.on('user_selector:selectionchanged', function(isempty) {
       add.set('disabled', isempty)
    });

    let remove = Y.one('#remove');
    let removeselect = M.core_user.get_user_selector('removeselect');
    remove.set('disabled', removeselect.is_selection_empty());
    removeselect.on('user_selector:selectionchanged', function(isempty){
       remove.set('disabled', isempty);
    });

    addselect = document.getElementById('addselect');
    addselect.onchange = updateUserSummary;
}