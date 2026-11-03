(function () {
    let toggleButton = document.querySelector('.sidebar-toggle-button');
    if (toggleButton) {
        toggleButton.addEventListener('click', sidebarToggleShow);
        let url = '/assets/global/icon-sidebar.svg';
        let svgToggleButton = getSVGforURL(url);
        svgToggleButton.then(svg => {
            if (svg) {
                toggleButton.replaceWith(svg);
                svg.addEventListener('click', sidebarToggleShow);
                svg.classList.add('sidebar-toggle-button');
            } else {
                console.log(`NO se logro cargar la imagen: ${URL_APP + url}`)
            }
        });
    }
})();

function sidebarToggleShow(event) {
    const sidebarContain = document.querySelector('.sidebar-contain');
    const sidebarButton = document.querySelector('.sidebar-toggle-button');
    if (sidebarContain) {
        sidebarContain.classList.toggle('sidebar-contain-hidden');
        //sidebarContain.classList.toggle('sidebar-contain-show');
        sidebarButton.classList.toggle('sidebar-toggle-button-closed');
    }
}

function addOptionSidebar(elementConfig = { tag: '', urlIcon: '', href: '', isSelected: false }) {
    try {
        const sidebarMenu = document.querySelector('.sidebar-menu');
        if (!sidebarMenu) {
            console.log('NO se logro encontrar el menu lateral');
            return null;
        }

        const existingOption = sidebarMenu.querySelector(`[data-menu-key="${CSS.escape(elementConfig.tag)}"]`);
        if (existingOption) {
            if (elementConfig.isSelected) {
                existingOption.classList.add('option-menu-selected');
            }
            return existingOption;
        }

        const newOption = document.createElement('div');
        newOption.classList.add('option-menu');
        newOption.dataset.menuKey = elementConfig.tag;

        if (elementConfig.isSelected) {
            newOption.classList.add('option-menu-selected');
        }

        newOption.onclick = function () {
            window.location.href = elementConfig.href;
        };

        newOption.innerHTML = `
            <img src="${elementConfig.urlIcon}" alt="${elementConfig.tag}">
            <a>${elementConfig.tag}</a>
        `;

        const svgUrlIcon = getSVGforURL(elementConfig.urlIcon);
        svgUrlIcon.then(svg => {
            if (svg) {
                newOption.replaceChild(svg, newOption.querySelector('img'));
            } else {
                console.log(`NO se logro cargar la imagen: ${URL_APP + elementConfig.urlIcon}`)
            }
        });

        sidebarMenu.appendChild(newOption);
        return newOption;
    } catch (e) {
        console.log(e);
        return null;
    }
}