/* Mobile Nav - Kofflerboats.com */
(() => {
  let childMenuOpen = false;

  document.addEventListener("DOMContentLoaded", (event) => {
    // PC, Tablet Responsive Nav
    const topNav = "top-nav-container";
    if (
      document.body.classList.contains("isNotMobilePhone") &&
      document.getElementById(topNav)
    ) {
      initNavigation(topNav);
    }

    // Mobile Phone Nav
    const phoneNav = "mobile-menu-container";
    if (
      document.body.classList.contains("isMobilePhone") &&
      document.getElementById(phoneNav)
    ) {
      initNavigation(phoneNav);
    }
  });

  function initNavigation(containerId) {
    const container = document.getElementById(containerId);
    const navigation = container.querySelector("ul.responsive-nav");

    if (navigation === null) {
      console.error(
        "Site menu does not exists. Cannot initialize drop-down menus."
      );
      return;
    }

    initNavigationSubMenus(container, navigation);
    initNavOpener(containerId, container);
  }

  function initNavigationSubMenus(navContainer, navigation) {
    let subMenuLinks = navigation.querySelectorAll(".menu-item-has-children");
    subMenuLinks = [...subMenuLinks];
    subMenuLinks.forEach((subLink) => {
      subLink.addEventListener("click", (e) => {
        toggleMobileSubNav(e, navContainer);
      });
    });
  }

  function initNavOpener(containerId, navContainer) {
    const hamburgerButton = navContainer.querySelector(".hamburger-box");
    hamburgerButton.style.opacity = 1;
    hamburgerButton.parentNode.style.background = "transparent";

    let navMenuToggler = null;
    if ("top-nav-container" === containerId) {
      navMenuToggler = navContainer.getElementById("responsive-nav-opener");
    } else if ("mobile-menu-container" === containerId) {
      navMenuToggler = navContainer.getElementById("mobile-nav-opener");
    }

    if (null !== navMenuToggler) {
      navMenuToggler.addEventListener("click", (e) => {
        if (e.target === navMenuToggler || navMenuToggler.contains(e.target)) {
          toggleNavMenu(navMenuToggler, navContainer);
        }
      });
    }
    escapeKeyCloseMenuInit(navMenuToggler, navContainer);
  }

  function toggleNavMenu(navMenuToggler, navContainer) {
    const hamburger = navMenuToggler.querySelector(".hamburger");
    if (navContainer.classList.contains("menu-open")) {
      if (false === childMenuOpen) {
        // close menu
        navMenuToggler.classList.remove("menu-open");
        navContainer.classList.remove("menu-open");
        hamburger.classList.remove("is-active");
      } else {
        // close submenu
        deactivateSubMenu(navContainer);
      }
    } else {
      navMenuToggler.classList.add("menu-open");
      navContainer.classList.add("menu-open");
      hamburger.classList.add("is-active");
    }
  }

  function toggleMobileSubNav(e, navContainer) {
    e.preventDefault();

    const menuContainer = e.target.closest(".main-nav.responsive-nav");
    const parentLi = e.target.closest("li.menu-item-has-children");
    const subMenu = parentLi.querySelector(".sub-menu");

    if (subMenu.classList.contains("menu-open")) {
      deactivateSubMenu(navContainer);
    } else {
      const hamburger = navContainer.querySelector(".hamburger");
      hamburger.classList.add("hamburger--arrowalt-r");
      hamburger.classList.remove("hamburger--spin");
      parentLi.setAttribute("data-submenu", "open");
      subMenu.classList.add("menu-open");
      activateSubMenu(menuContainer, parentLi, subMenu);
    }
  }

  function activateSubMenu(menuContainer, parentLi, subMenu) {
    childMenuOpen = true;

    const openSubMenu = document.createElement("div");
    openSubMenu.classList.add("mobile-child-menu");

    const parentLiText = parentLi.childNodes[0].textContent;
    const parentLiUrl = parentLi.childNodes[0].href;

    const openSubMenuHeader = document.createElement("h3");
    openSubMenuHeader.textContent = parentLiText;

    const parentLinkLi = document.createElement("li");
    parentLinkLi.classList.add(
      "menu-item",
      "menu-item-type-post_type",
      "menu-item-object-page",
      "menu-item-child-parent"
    );

    const parentLink = document.createElement("a");
    parentLink.href = parentLiUrl;

    const parentLinkSpan = document.createElement("span");
    parentLinkSpan.classList.add("menu-item-label");
    parentLinkSpan.textContent = parentLiText;

    parentLink.append(parentLinkSpan);
    parentLinkLi.append(parentLink);

    const subMenuClone = subMenu.cloneNode(true);
    subMenuClone.prepend(parentLinkLi);
    openSubMenu.append(openSubMenuHeader, subMenuClone);
    menuContainer.after(openSubMenu);

    // setTimeout ensures the slide-in animation runs
    setTimeout(function () {
      openSubMenu.classList.add("open");
    }, 0);
  }

  function deactivateSubMenu(navContainer) {
    childMenuOpen = false;
    const menuContainer = navContainer.querySelector(
      ".main-nav.responsive-nav"
    );

    const parentLi = navContainer.querySelector(
      "li.menu-item-has-children[data-submenu='open']"
    );

    const hamburger = navContainer.querySelector(".hamburger");
    hamburger.classList.add("hamburger--spin");
    hamburger.classList.remove("hamburger--arrowalt-r");

    const subMenu = parentLi.querySelector(".sub-menu");
    const openSubMenu =
      menuContainer.parentNode.querySelector(".mobile-child-menu");
    openSubMenu.classList.remove("open");

    // setTimeout allows close animation to run before removing submenu element
    setTimeout(function () {
      openSubMenu.remove();
    }, 500);

    parentLi.removeAttribute("data-submenu");
    subMenu.classList.remove("menu-open");
  }

  function escapeKeyCloseMenuInit(navMenuToggler, navContainer) {
    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        if (
          false === childMenuOpen &&
          navContainer.classList.contains("menu-open")
        ) {
          // close menu
          toggleNavMenu(navMenuToggler, navContainer);
        } else {
          // close sub menu
          deactivateSubMenu(navContainer);
        }
      }
    });
  }
})();
