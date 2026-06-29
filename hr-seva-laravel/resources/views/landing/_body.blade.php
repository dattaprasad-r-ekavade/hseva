<!--  Search modal -->
    <div id="uc-search-modal" class="uc-modal-full uc-modal" data-uc-modal="overlay: true">
        <div class="uc-modal-dialog d-flex justify-center bg-white text-dark dark:bg-gray-900 dark:text-white"
            data-uc-height-viewport="">

            <div class="uc-modal-close-full m-1 p-0 vstack gap-narrow text-center">
                <button
                    class="icon-3 btn btn-md btn-dark dark:bg-white dark:text-dark w-24px sm:w-32px h-24px sm:h-32px rounded-circle flex-1"
                    type="button">
                    <i class="unicon-close"></i>
                </button>
                <span class="ft-tertiary fs-7">ESC</span>
            </div>

            <div class="panel w-100 sm:w-500px px-2 py-10">
                <h3 class="h4 sm:h2 text-center">Search</h3>
                <form class="hstack gap-1 mt-4 border-bottom p-narrow dark:border-gray-700" action="?">
                    <span
                        class="d-inline-flex justify-center items-center w-24px sm:w-40 h-24px sm:h-40px opacity-50"><i
                            class="unicon-search icon-3"></i></span>
                    <input type="search" name="q"
                        class="form-control-plaintext ltr:ms-1 rtl:me-1 fs-6 sm:fs-5 w-full dark:text-white"
                        placeholder="Type your keyword.." aria-label="Search" autofocus>
                </form>
            </div>
        </div>
    </div>

    <!--  Newsletter modal -->
    <div id="uc-newsletter-modal" data-uc-modal="overlay: true">
        <div
            class="uc-modal-dialog w-800px bg-white text-dark dark:bg-gray-900 dark:text-white rounded-3 p-1 overflow-hidden">
            <button
                class="uc-modal-close-default p-0 icon-3 btn border-0 dark:text-white dark:text-opacity-50 hover:text-primary hover:rotate-90 duration-150 transition-all"
                type="button">
                <i class="unicon-close"></i>
            </button>
            <div class="row md:child-cols-6 col-match g-0">
                <div class="d-none md:d-flex">
                    <div class="position-relative w-100 ratio-1x1 rounded-2 overflow-hidden">
                        <img class="media-cover" src="assets-02/images/common/newsletter.jpg" alt="Newsletter image">
                    </div>
                </div>
                <div>
                    <div class="panel vstack self-center p-4 md:py-8 text-center">
                        <h3 class="h3 md:h2">Subscribe to our Newsletter</h3>
                        <p class="ft-tertiary">Join 10k+ people to get notified about new posts, news and updates.</p>
                        <div class="panel mt-2 lg:mt-4">
                            <form class="vstack gap-1">
                                <input type="email"
                                    class="form-control form-control-sm w-full fs-6 bg-white dark:border-white dark:border-gray-700"
                                    placeholder="Your email address..">
                                <button type="submit" class="btn btn-sm btn-primary">Sign up</button>
                            </form>
                            <p class="fs-7 mt-2">Do not worry we don't spam!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--  Acccount modal -->
    <div id="uc-account-modal" data-uc-modal="overlay: true">
        <div class="uc-modal-dialog lg:max-w-500px bg-secondary text-dark dark:bg-gray-800 dark:text-white rounded">

            <button
                class="uc-modal-close-default top-0 ltr:end-0 rtl:start-0 rtl:end-auto m-2 p-0 border-0 icon-2 lg:icon-3 btn btn-md dark:text-white transition-transform duration-150 hover:rotate-90"
                type="button">
                <i class="unicon-close"></i>
            </button>

            <div class="panel vstack gap-2 md:gap-4 text-center">
                <ul class="account-tabs-nav nav-x justify-center h6 py-2 border-bottom d-none"
                    data-uc-switcher="animation: uc-animation-slide-bottom-small, uc-animation-slide-top-small">
                    <li><a href="#">Sign in</a></li>
                    <li><a href="#">Sign up</a></li>
                    <li><a href="#">Reset password</a></li>
                    <li><a href="#">Terms of use</a></li>
                </ul>
                <div
                    class="account-tabs-content uc-switcher px-3 lg:px-4 py-4 lg:py-8 m-0 lg:mx-auto vstack justify-center items-center">
                    <div class="w-100">
                        <div class="panel vstack justify-center items-center gap-2 sm:gap-4 text-center">
                            <h4 class="h5 lg:h4 m-0">Log in</h4>
                            <div class="panel vstack gap-4 w-100 sm:w-350px mx-auto">
                                <form class="vstack gap-2">
                                    <input class="form-control h-48px w-full bg-white dark:border-white dark:text-dark"
                                        type="email" placeholder="Your email" required>
                                    <input class="form-control h-48px w-full bg-white dark:border-white dark:text-dark"
                                        type="password" placeholder="Password" required>
                                    <div class="hstack justify-between items-start text-start">
                                        <div class="form-check text-start rtl:text-end">
                                            <input
                                                class="form-check-input rounded-0 dark:bg-gray-900 dark:text-white dark:border-gray-700"
                                                type="checkbox" id="inputCheckRemember">
                                            <label class="hstack justify-between form-check-label fs-7 sm:fs-6"
                                                for="inputCheckRemember">Remember me?</label>
                                        </div>
                                        <a href="#" class="uc-link" data-uc-switcher-item="2">Forgot password</a>
                                    </div>
                                    <button class="btn btn-primary btn-md text-white lg:mt-2" type="submit">Log
                                        in</button>
                                </form>
                                <div class="panel">
                                    <hr class="m-0">
                                    <span
                                        class="position-absolute top-50 start-50 translate-middle p-1 fs-7 text-uppercase bg-white dark:bg-gray-800">Or</span>
                                </div>
                                <div class="hstack gap-2">
                                    <a href="#google"
                                        class="hstack items-center justify-center flex-1 gap-1 h-48px text-none rounded border border-gray-900 dark:border-white border-opacity-10">
                                        <i class="icon icon-1 unicon-logo-google"></i>
                                    </a>
                                    <a href="#facebook"
                                        class="hstack items-center justify-center flex-1 gap-1 h-48px text-none rounded border border-gray-900 dark:border-white border-opacity-10">
                                        <i class="icon icon-1 unicon-logo-facebook"></i>
                                    </a>
                                    <a href="#x"
                                        class="hstack items-center justify-center flex-1 gap-1 h-48px text-none rounded border border-gray-900 dark:border-white border-opacity-10">
                                        <i class="icon icon-1 unicon-logo-x-filled"></i>
                                    </a>
                                </div>
                            </div>
                            <p class="fs-7 sm:fs-6">Have no account yet? <a class="uc-link" href="#"
                                    data-uc-switcher-item="1">Sign up</a></p>
                        </div>
                    </div>
                    <div class="w-100">
                        <div class="panel vstack justify-center items-center gap-2 sm:gap-4 text-center">
                            <h4 class="h5 lg:h4 m-0">Create an account</h4>
                            <div class="panel vstack gap-4 w-100 sm:w-350px mx-auto">
                                <form class="vstack gap-2">
                                    <input class="form-control h-48px w-full bg-white dark:border-white dark:text-dark"
                                        type="text" placeholder="Full name" required>
                                    <input class="form-control h-48px w-full bg-white dark:border-white dark:text-dark"
                                        type="email" placeholder="Your email" required>
                                    <input class="form-control h-48px w-full bg-white dark:border-white dark:text-dark"
                                        type="password" placeholder="Password" required>
                                    <input class="form-control h-48px w-full bg-white dark:border-white dark:text-dark"
                                        type="password" placeholder="Re-enter Password" required>
                                    <div class="hstack text-start">
                                        <div class="form-check text-start rtl:text-end">
                                            <input
                                                class="form-check-input rounded-0 dark:bg-gray-900 dark:text-white dark:border-gray-700"
                                                type="checkbox" required>
                                            <label class="hstack justify-between form-check-label fs-7 sm:fs-6">I read
                                                and accept the <a href="#" class="uc-link ltr:ms-narrow rtl:me-narrow"
                                                    data-uc-switcher-item="3">terms of use</a>. </label>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary btn-md text-white lg:mt-2" type="submit">Sign
                                        up</button>
                                </form>
                                <div class="panel">
                                    <hr class="m-0">
                                    <span
                                        class="position-absolute top-50 start-50 translate-middle p-1 fs-7 text-uppercase bg-white dark:bg-gray-800">Or</span>
                                </div>
                                <div class="hstack gap-2">
                                    <a href="#google"
                                        class="hstack items-center justify-center flex-1 gap-1 h-48px text-none rounded border border-gray-900 dark:border-white border-opacity-10">
                                        <i class="icon icon-1 unicon-logo-google"></i>
                                    </a>
                                    <a href="#facebook"
                                        class="hstack items-center justify-center flex-1 gap-1 h-48px text-none rounded border border-gray-900 dark:border-white border-opacity-10">
                                        <i class="icon icon-1 unicon-logo-facebook"></i>
                                    </a>
                                    <a href="#x"
                                        class="hstack items-center justify-center flex-1 gap-1 h-48px text-none rounded border border-gray-900 dark:border-white border-opacity-10">
                                        <i class="icon icon-1 unicon-logo-x-filled"></i>
                                    </a>
                                </div>
                            </div>
                            <p class="fs-7 sm:fs-6">Already have an account? <a class="uc-link" href="#"
                                    data-uc-switcher-item="0">Log in</a></p>
                        </div>
                    </div>
                    <div class="w-100">
                        <div class="panel vstack justify-center items-center gap-2 sm:gap-4 text-center">
                            <h4 class="h5 lg:h4 m-0">Reset password</h4>
                            <div class="panel w-100 sm:w-350px">
                                <form class="vstack gap-2">
                                    <input class="form-control h-48px w-full bg-white dark:border-white dark:text-dark"
                                        type="email" placeholder="Your email" required>
                                    <div class="form-check text-start rtl:text-end">
                                        <input
                                            class="form-check-input rounded-0 dark:bg-gray-900 dark:text-white dark:border-gray-700"
                                            type="checkbox" id="inputCheckVerify" required>
                                        <label class="form-check-label fs-7 sm:fs-6" for="inputCheckVerify">
                                            <span>I'm not a robot</span>.
                                        </label>
                                    </div>
                                    <button class="btn btn-primary btn-md text-white lg:mt-2" type="submit">Reset a
                                        password</button>
                                </form>
                            </div>
                            <p class="fs-7 sm:fs-6 mt-2 sm:m-0">Remember your password? <a class="uc-link" href="#"
                                    data-uc-switcher-item="0">Log in</a></p>
                        </div>
                    </div>
                    <div class="w-100">
                        <div class="panel vstack justify-center items-center gap-2 sm:gap-4">
                            <h4 class="h5 lg:h4 m-0">Terms of use</h4>
                            <div class="page-content panel fs-6 text-start max-h-400px overflow-scroll">
                                <p>Terms of use dolor sit amet consectetur, adipisicing elit. Recusandae provident ullam
                                    aperiam quo ad non corrupti sit vel quam repellat ipsa quod sed, repellendus
                                    adipisci, ducimus ea modi odio assumenda.</p>
                                <h5 class="h6 md:h5 mt-3 mb-1">Disclaimers</h5>
                                <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Sequi, cum esse possimus
                                    officiis amet ea voluptatibus libero! Dolorum assumenda esse, deserunt ipsum ad
                                    iusto! Praesentium error nobis tenetur at, quis nostrum facere excepturi architecto
                                    totam.</p>
                                <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Inventore, soluta alias
                                    eaque modi ipsum sint iusto fugiat vero velit rerum.</p>
                                <h5 class="h6 md:h5 mt-3 mb-1">Limitation on Liability</h5>
                                <p>Sequi, cum esse possimus officiis amet ea voluptatibus libero! Dolorum assumenda
                                    esse, deserunt ipsum ad iusto! Praesentium error nobis tenetur at, quis nostrum
                                    facere excepturi architecto totam.</p>
                                <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Inventore, soluta alias
                                    eaque modi ipsum sint iusto fugiat vero velit rerum.</p>
                                <h5 class="h6 md:h5 mt-3 mb-1">Copyright Policy</h5>
                                <p>Dolor sit amet consectetur adipisicing elit. Sequi, cum esse possimus officiis amet
                                    ea voluptatibus libero! Dolorum assumenda esse, deserunt ipsum ad iusto! Praesentium
                                    error nobis tenetur at, quis nostrum facere excepturi architecto totam.</p>
                                <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Inventore, soluta alias
                                    eaque modi ipsum sint iusto fugiat vero velit rerum.</p>
                                <h5 class="h6 md:h5 mt-3 mb-1">General</h5>
                                <p>Sit amet consectetur adipisicing elit. Sequi, cum esse possimus officiis amet ea
                                    voluptatibus libero! Dolorum assumenda esse, deserunt ipsum ad iusto! Praesentium
                                    error nobis tenetur at, quis nostrum facere excepturi architecto totam.</p>
                                <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Inventore, soluta alias
                                    eaque modi ipsum sint iusto fugiat vero velit rerum.</p>
                            </div>
                            <p class="fs-7 sm:fs-6">Do you agree to our terms? <a class="uc-link" href="#"
                                    data-uc-switcher-item="1">Sign up</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--  Menu panel -->
    <div id="uc-menu-panel" data-uc-offcanvas="overlay: true;">
        <div class="uc-offcanvas-bar bg-white text-dark dark:bg-gray-900 dark:text-white">

            <header class="uc-offcanvas-header hstack justify-between items-center pb-2 bg-white dark:bg-gray-900">
                <div class="uc-logo">
                    <a href="/" class="h5 text-none text-gray-900 dark:text-white">
                        <img src="assets-02/images/common/logo-new-light.svg" alt="HR Seva" style="width: 120px; height: auto;">
                    </a>
                </div>
                <button
                    class="uc-offcanvas-close rtl:end-auto rtl:start-0 m-1 mt-2 icon-3 btn border-0 dark:text-white dark:text-opacity-50 hover:text-primary hover:rotate-90 duration-150 transition-all"
                    type="button">
                    <i class="unicon-close"></i>
                </button>
            </header>

            <div class="panel">
                <ul class="nav-y gap-narrow fw-medium fs-6" data-uc-nav>
                    <li><a href="#hero_header">Home</a></li>
                    <li><a href="#main_features">Services</a></li>
                    <li><a href="#key_features">Why HR Seva</a></li>
                    <li><a href="#pricing_tiers">Pricing</a></li>
                    <li><a href="#faq">FAQs</a></li>
                    <li><a href="#uc_cta">Contact</a></li>
                    <li class="hr opacity-10 my-1"></li>
                    <li><a href="#pricing_tiers">View plans</a></li>
                    <li><a href="#uc-contact-modal" data-uc-toggle>Start free trial</a></li>
                </ul>
                <ul class="social-icons nav-x mt-4">
                    <li>
                        <a href="#"><i class="unicon-logo-medium icon-2"></i></a>
                        <a href="#"><i class="unicon-logo-x-filled icon-2"></i></a>
                        <a href="#"><i class="unicon-logo-instagram icon-2"></i></a>
                        <a href="#"><i class="unicon-logo-pinterest icon-2"></i></a>
                    </li>
                </ul>
            </div>

        </div>
    </div>

    <!--  Cart panel -->
    <div id="uc-cart-panel" data-uc-offcanvas="overlay: true; flip: true;">
        <div class="uc-offcanvas-bar bg-white text-dark dark:bg-gray-900 dark:text-white">

            <button
                class="uc-offcanvas-close top-0 ltr:end-0 rtl:start-0 rtl:end-auto m-2 p-0 border-0 icon-2 lg:icon-3 btn btn-md dark:text-white transition-transform duration-150 hover:rotate-90"
                type="button">
                <i class="unicon-close"></i>
            </button>

            <div class="mini-cart-content vstack justify-between panel h-100">
                <div class="mini-cart-header">
                    <h3 class="title h5 m-0 text-dark dark:bg-gray-900">Shopping cart</h3>
                </div>
                <div class="mini-cart-listing panel flex-1 my-4 overflow-scroll">
                    <p class="alert alert-warning" hidden>Your cart empty!</p>
                    <div class="panel vstack gap-3">
                        <div>
                            <article class="product type-product panel">
                                <div class="hstack gap-2">
                                    <figure
                                        class="featured-image m-0 rounded ratio ratio-1x1 w-80px uc-transition-toggle overflow-hidden">
                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                            src="assets-02/images/common/products/img-07.jpg" alt="Laptop Cover">
                                        <a href="#" class="position-cover"
                                            data-caption="Laptop Cover"></a>
                                    </figure>
                                    <div class="content vstack gap-narrow fs-6">
                                        <h5 class="h6 m-0"><a class="text-none text-dark dark:text-white"
                                                href="#">Laptop Cover</a></h5>
                                        <div class="hstack gap-narrow fs-7 opacity-50 text-dark dark:text-white">
                                            <span class="qty">1</span> x <span class="price">$24.00</span>
                                        </div>
                                        <a href="#remove_from_cart"
                                            class="remove fs-7 text-dark dark:text-white">Remove</a>
                                    </div>
                                    <a href="#remove_from_cart"
                                        class="remove position-absolute top-0 end-0 btn p-0 hover:text-danger" hidden>
                                        <i class="unicon-close icon-1"></i>
                                    </a>
                                </div>
                            </article>
                        </div>
                        <div>
                            <article class="product type-product panel">
                                <div class="hstack gap-2">
                                    <figure
                                        class="featured-image m-0 rounded ratio ratio-1x1 w-80px uc-transition-toggle overflow-hidden">
                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                            src="assets-02/images/common/products/img-08.jpg" alt="Disney Toys">
                                        <a href="#" class="position-cover"
                                            data-caption="Disney Toys"></a>
                                    </figure>
                                    <div class="content vstack gap-narrow fs-6">
                                        <h5 class="h6 m-0"><a class="text-none text-dark dark:text-white"
                                                href="#">Disney Toys</a></h5>
                                        <div class="hstack gap-narrow fs-7 opacity-50 text-dark dark:text-white">
                                            <span class="qty">1</span> x <span class="price">$5.00</span>
                                        </div>
                                        <a href="#remove_from_cart"
                                            class="remove fs-7 text-dark dark:text-white">Remove</a>
                                    </div>
                                    <a href="#remove_from_cart"
                                        class="remove position-absolute top-0 end-0 btn p-0 hover:text-danger" hidden>
                                        <i class="unicon-close icon-1"></i>
                                    </a>
                                </div>
                            </article>
                        </div>
                        <div>
                            <article class="product type-product panel">
                                <div class="hstack gap-2">
                                    <figure
                                        class="featured-image m-0 rounded ratio ratio-1x1 w-80px uc-transition-toggle overflow-hidden">
                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                            src="assets-02/images/common/products/img-09.jpg" alt="Screen Axe">
                                        <a href="#" class="position-cover"
                                            data-caption="Screen Axe"></a>
                                    </figure>
                                    <div class="content vstack gap-narrow fs-6">
                                        <h5 class="h6 m-0"><a class="text-none text-dark dark:text-white"
                                                href="#">Screen Axe</a></h5>
                                        <div class="hstack gap-narrow fs-7 opacity-50 text-dark dark:text-white">
                                            <span class="qty">1</span> x <span class="price">$19.00</span>
                                        </div>
                                        <a href="#remove_from_cart"
                                            class="remove fs-7 text-dark dark:text-white">Remove</a>
                                    </div>
                                    <a href="#remove_from_cart"
                                        class="remove position-absolute top-0 end-0 btn p-0 hover:text-danger" hidden>
                                        <i class="unicon-close icon-1"></i>
                                    </a>
                                </div>
                            </article>
                        </div>
                        <div>
                            <article class="product type-product panel">
                                <div class="hstack gap-2">
                                    <figure
                                        class="featured-image m-0 rounded ratio ratio-1x1 w-80px uc-transition-toggle overflow-hidden">
                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                            src="assets-02/images/common/products/img-10.jpg" alt="Airpods Pro">
                                        <a href="#" class="position-cover"
                                            data-caption="Airpods Pro"></a>
                                    </figure>
                                    <div class="content vstack gap-narrow fs-6">
                                        <h5 class="h6 m-0"><a class="text-none text-dark dark:text-white"
                                                href="#">Airpods Pro</a></h5>
                                        <div class="hstack gap-narrow fs-7 opacity-50 text-dark dark:text-white">
                                            <span class="qty">1</span> x <span class="price">$49.00</span>
                                        </div>
                                        <a href="#remove_from_cart"
                                            class="remove fs-7 text-dark dark:text-white">Remove</a>
                                    </div>
                                    <a href="#remove_from_cart"
                                        class="remove position-absolute top-0 end-0 btn p-0 hover:text-danger" hidden>
                                        <i class="unicon-close icon-1"></i>
                                    </a>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
                <div class="mini-cart-footer panel pt-3 border-top">
                    <div class="panel vstack gap-3 justify-between">
                        <div class="mini-cart-total hstack justify-between">
                            <h5 class="h5 m-0 text-dark dark:text-white">Subtotal</h5>
                            <b class="fs-5">$97.00</b>
                        </div>
                        <div class="mini-cart-actions vstack gap-1">
                            <a href="#"
                                class="btn btn-md btn-outline-gray-100 text-dark dark:text-white dark:border-gray-700 dark:hover:bg-gray-700">View
                                cart</a>
                            <a href="#uc-contact-modal" data-uc-toggle class="btn btn-md btn-primary text-white">Checkout</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!--  Favorites modal -->
    <div id="uc-favorites-panel" data-uc-modal="overlay: true">
        <div class="uc-modal-dialog lg:max-w-500px bg-white text-dark dark:bg-gray-800 dark:text-white rounded">

            <button
                class="uc-modal-close-default top-0 ltr:end-0 rtl:start-0 rtl:end-auto m-2 p-0 border-0 icon-2 lg:icon-3 btn btn-md dark:text-white transition-transform duration-150 hover:rotate-90"
                type="button">
                <i class="unicon-close"></i>
            </button>

            <div class="panel vstack justify-center items-center gap-2 text-center py-8">
                <i class="icon icon-4 unicon-bookmark mb-2 text-primary dark:text-white"></i>
                <h2 class="h4 md:h3 m-0">Your favorites</h2>
                <p class="fs-5 opacity-60">You have not yet added any recipe to your favorites list.</p>
                <a href="#"
                    class="btn btn-md btn-outline-gray-100 text-dark dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 mt-2 uc-modal-close">Browse
                    recipes</a>
            </div>
        </div>
    </div>

    <!--  Contact modal -->
    <div id="uc-contact-modal" data-uc-modal="overlay: true">
        <div class="uc-modal-dialog lg:max-w-650px bg-secondary text-dark border border-primary-100 shadow-lg dark:bg-gray-800 dark:text-white rounded-1-5">

            <button
                class="uc-modal-close-default top-0 ltr:end-0 rtl:start-0 rtl:end-auto m-2 p-0 border-0 icon-2 lg:icon-3 btn btn-md text-primary dark:text-white transition-transform duration-150 hover:rotate-90"
                type="button">
                <i class="unicon-close"></i>
            </button>

            <div class="panel vstack gap-2 md:gap-4 text-center">
                <div class="panel cstack px-3 md:px-4 py-4 md:py-8 m-0 lg:mx-auto">
                    <div class="panel vstack justify-center items-center gap-2 sm:gap-4 text-center">
                        <h4 class="h5 lg:h4 m-0 text-primary">Start your free trial</h4>
                        <div class="panel w-100 sm:w-350px md:w-500px mx-auto">
                            <form id="freeTrialForm" class="vstack gap-2" novalidate>
                                <div class="hstack justify-center gap-1 flex-wrap mb-2">
                                    <span class="rounded-pill px-1 py-narrow border border-primary bg-primary text-tertiary fs-8 fw-bold" data-trial-indicator="0">1. Contact</span>
                                    <span class="rounded-pill px-1 py-narrow border border-primary-200 bg-secondary text-primary fs-8 fw-bold" data-trial-indicator="1">2. Company</span>
                                </div>
                                <div class="vstack gap-2 text-start" data-trial-step="0">
                                    <p class="fs-7 text-primary text-opacity-70 text-center mb-1">Share your primary contact details.</p>
                                    <input
                                        class="form-control h-48px w-100 bg-white border-primary-100 dark:border-white dark:text-dark"
                                        type="text" id="trialFullName" placeholder="Full Name*" required>
                                    <input
                                        class="form-control h-48px w-100 bg-white border-primary-100 dark:border-white dark:text-dark"
                                        type="email" id="trialEmail" placeholder="Business email*" required>
                                    <input
                                        class="form-control h-48px w-100 rtl:text-end bg-white border-primary-100 dark:border-white dark:text-dark"
                                        type="tel" id="trialPhone" placeholder="Phone Number*" required>
                                </div>

                                <div class="vstack gap-2 text-start" data-trial-step="1" hidden>
                                    <p class="fs-7 text-primary text-opacity-70 text-center mb-1">Collect company details for the request.</p>
                                    <input class="form-control h-48px w-full bg-white border-primary-100 dark:border-white dark:text-dark"
                                        type="text" id="trialCompanyName" placeholder="Company name*" required>
                                    <select class="form-control h-48px w-full bg-white border-primary-100 text-primary dark:border-white dark:text-dark"
                                            id="trialTeamSize" required>
                                            <option value="">Select Company Size*</option>
                                            <option value="1-10">1-10</option>
                                            <option value="11-25">11-25</option>
                                            <option value="26-50">26-50</option>
                                            <option value="51-100">51-100</option>
                                            <option value="100+">100+</option>
                                    </select>
                                    <select class="form-control h-48px w-full bg-white border-primary-100 text-primary dark:border-white dark:text-dark"
                                            id="trialPlan" required>
                                            <option value="">Select Plan*</option>
                                            <option value="Free Trial">Free Trial</option>
                                            <option value="Starter">Starter</option>
                                            <option value="Premium">Premium</option>
                                    </select>
                                    <select class="form-control h-48px w-full bg-white border-primary-100 text-primary dark:border-white dark:text-dark"
                                            id="trialState" required>
                                            <option value="">Select State*</option>
                                            <option value="Andhra Pradesh">Andhra Pradesh</option>
                                            <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                                            <option value="Assam">Assam</option>
                                            <option value="Bihar">Bihar</option>
                                            <option value="Chhattisgarh">Chhattisgarh</option>
                                            <option value="Goa">Goa</option>
                                            <option value="Gujarat">Gujarat</option>
                                            <option value="Haryana">Haryana</option>
                                            <option value="Himachal Pradesh">Himachal Pradesh</option>
                                            <option value="Jharkhand">Jharkhand</option>
                                            <option value="Karnataka">Karnataka</option>
                                            <option value="Kerala">Kerala</option>
                                            <option value="Madhya Pradesh">Madhya Pradesh</option>
                                            <option value="Maharashtra">Maharashtra</option>
                                            <option value="Manipur">Manipur</option>
                                            <option value="Meghalaya">Meghalaya</option>
                                            <option value="Mizoram">Mizoram</option>
                                            <option value="Nagaland">Nagaland</option>
                                            <option value="Odisha">Odisha</option>
                                            <option value="Punjab">Punjab</option>
                                            <option value="Rajasthan">Rajasthan</option>
                                            <option value="Sikkim">Sikkim</option>
                                            <option value="Tamil Nadu">Tamil Nadu</option>
                                            <option value="Telangana">Telangana</option>
                                            <option value="Tripura">Tripura</option>
                                            <option value="Uttar Pradesh">Uttar Pradesh</option>
                                            <option value="Uttarakhand">Uttarakhand</option>
                                            <option value="West Bengal">West Bengal</option>
                                            <option value="Andaman and Nicobar Islands">Andaman and Nicobar Islands</option>
                                            <option value="Chandigarh">Chandigarh</option>
                                            <option value="Dadra and Nagar Haveli and Daman and Diu">Dadra and Nagar Haveli and Daman and Diu</option>
                                            <option value="Delhi">Delhi</option>
                                            <option value="Jammu and Kashmir">Jammu and Kashmir</option>
                                            <option value="Ladakh">Ladakh</option>
                                            <option value="Lakshadweep">Lakshadweep</option>
                                            <option value="Puducherry">Puducherry</option>
                                    </select>
                                    <textarea class="form-control min-h-80px w-full bg-white border-primary-100 dark:border-white dark:text-dark"
                                        id="trialAddress" placeholder="Full Address*" required></textarea>
                                    <input class="form-control h-48px w-full bg-white border-primary-100 dark:border-white dark:text-dark"
                                        type="text" id="trialLocation" placeholder="City*" required>
                                    <input class="form-control h-48px w-full bg-white border-primary-100 dark:border-white dark:text-dark"
                                        type="text" id="trialPincode" placeholder="Pincode*" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required>
                                    <input class="form-control h-48px w-full bg-white border-primary-100 dark:border-white dark:text-dark"
                                        type="text" id="trialWebsiteUrl" placeholder="Website URL">
                                </div>

                                <div id="freeTrialFormMessage" class="fs-7 text-center text-primary min-h-24px"></div>
                                <div class="hstack justify-between gap-2 mt-2">
                                    <button class="btn btn-md btn-secondary border border-primary-100 text-primary hover:bg-secondary-300"
                                        type="button" id="freeTrialPrev" hidden>Back</button>
                                    <button class="btn btn-primary btn-md text-tertiary flex-1" type="submit" id="freeTrialSubmit">Submit request</button>
                                </div>
                                <p class="fs-7 text-primary text-opacity-70 mt-2 text-center">We'll tailor your free trial to your immediate needs and help your team get value quickly.</p>
                            </form>
                            <div id="freeTrialSuccessState" class="trial-success-card p-3 p-md-4 text-center" hidden>
                                <div class="trial-success-icon d-inline-flex align-items-center justify-content-center mx-auto mb-2 mt-2">
                                    <img src="assets-02/images/common/correct.svg" alt="Success">
                                </div>
                                <h5 class="h4 m-0 text-primary">Thank you!</h5>
                                <p class="fs-6 text-primary mt-1 mb-1">Your free trial request has been submitted successfully.</p>
                                <p class="fs-7 text-primary text-opacity-70 mb-2">Our team will contact you shortly on your email or phone number. Please keep your details available.</p>
                                <div class="vstack gap-1 mb-2">
                                    <div class="fs-7 text-primary">Our HR team will review your request.</div>
                                    <div class="fs-7 text-primary">We will help you with the right plan and onboarding support.</div>
                                </div>
                                <button type="button" class="btn btn-primary btn-md text-tertiary w-100 uc-modal-close" id="freeTrialSuccessDone">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy modal -->
    <div id="uc-privacy-modal" data-uc-modal="overlay: true">
        <div class="uc-modal-dialog lg:max-w-700px bg-white text-dark dark:bg-gray-900 dark:text-white rounded-2 p-2 lg:p-4">
            <button
                class="uc-modal-close-default top-0 ltr:end-0 rtl:start-0 rtl:end-auto m-2 p-0 border-0 icon-2 lg:icon-3 btn btn-md text-primary dark:text-white transition-transform duration-150 hover:rotate-90"
                type="button">
                <i class="unicon-close"></i>
            </button>
            <div class="panel vstack gap-2 p-2 lg:p-3">
                <h4 class="h5 lg:h4 m-0">Privacy Policy</h4>
                <p class="fs-6 opacity-80">HR Seva collects only the business and employee information needed to provide HR, payroll, attendance, and compliance support.</p>
                <p class="fs-6 opacity-80">Your data is stored with controlled access and used only for service delivery, reporting, support, and statutory process management.</p>
                <p class="fs-6 opacity-80">We do not sell your data. If you need changes, deletion requests, or more information about data handling, contact HR Seva through the consultation form on this page.</p>
            </div>
        </div>
    </div>

    <!-- Terms modal -->
    <div id="uc-terms-modal" data-uc-modal="overlay: true">
        <div class="uc-modal-dialog lg:max-w-700px bg-white text-dark dark:bg-gray-900 dark:text-white rounded-2 p-2 lg:p-4">
            <button
                class="uc-modal-close-default top-0 ltr:end-0 rtl:start-0 rtl:end-auto m-2 p-0 border-0 icon-2 lg:icon-3 btn btn-md text-primary dark:text-white transition-transform duration-150 hover:rotate-90"
                type="button">
                <i class="unicon-close"></i>
            </button>
            <div class="panel vstack gap-2 p-2 lg:p-3">
                <h4 class="h5 lg:h4 m-0">Terms of Use</h4>
                <p class="fs-6 opacity-80">By using HR Seva, you agree to provide accurate business information and use the platform and services only for lawful employment and payroll operations.</p>
                <p class="fs-6 opacity-80">Plan features, support scope, and filing assistance may vary by package. Compliance filing support applies only where documents and approvals are provided on time.</p>
                <p class="fs-6 opacity-80">For custom legal or contractual terms, HR Seva may share separate service agreements during onboarding.</p>
            </div>
        </div>
    </div>

    <!--  Bottom Actions Sticky -->
    <div
        class="backtotop-wrap position-fixed ltr:end-0 ltr:start-auto rtl:start-0 rtl:end-auto top-auto bottom-0 z-99 m-2 vstack">
        <a class="btn btn-sm bg-primary text-white w-40px h-40px rounded-circle" href="#" data-uc-backtotop>
            <i class="icon-2 unicon-chevron-up"></i>
        </a>
    </div>

    <!-- Header start -->
    <div class="uc-banner-top py-1 p-2 m-0 hide-on-sticky text-center bg-gradient-to-r from-primary dark:from-tertiary-200 to-primary-700 dark:to-tertiary text-white dark:text-dark"
        data-uc-alert="">
        <a href="#" class="uc-alert-close top-0 end-0" style="margin: 14px;" data-uc-close></a>
        <p>HR Seva now supports recruitment, payroll, and compliance in one seamless platform. <br
                class="d-block lg:d-none"> <a href="#uc_cta"
                class="uc-link text-tertiary dark:text-primary border-bottom">Book a consultation</a></p>
    </div>
    <header class="uc-header header-seven uc-navbar-sticky-wrap z-999 "
        data-uc-sticky="start: 1; show-on-up: true; animation: uc-animation-slide-top; sel-target: .uc-navbar-container; cls-active: uc-navbar-sticky; cls-inactive: uc-navbar-sticky; end: !*;">
        <nav class="uc-navbar-container uc-navbar-float ft-tertiary z-1"
            data-anime="translateY: [-40, 0]; opacity: [0, 1]; easing: easeOutExpo; duration: 750; delay: 0;">
            <div class="uc-navbar-main" style="--uc-nav-height: 64px">
                <div class="container">
                    <div class="uc-navbar min-h-64px text-gray-900 dark:text-white"
                        data-uc-navbar=" animation: uc-animation-slide-top-small; duration: 150;">
                        <div class="uc-navbar-left">
                            <div class="uc-logo">
                                <a class="panel text-none" href="/" style="width: 140px;">
                                    <img class="dark:d-none" src="assets-02/images/common/logo-new-light.svg"
                                        alt="HR Seva">
                                    <img class="d-none dark:d-block" src="assets-02/images/common/logo-new-dark.svg"
                                        alt="HR Seva">
                                </a>
                            </div>
                            <ul class="uc-navbar-nav gap-3 d-none lg:d-flex ltr:ms-2 rtl:me-2">
                                <li><a href="#hero_header">Home</a></li>
                                <li><a href="#main_features">Services</a></li>
                                <li><a href="#key_features">Why HR Seva</a></li>
                                <li><a href="#pricing_tiers">Pricing</a></li>
                                <li><a href="#faq">FAQs</a></li>
                                <li><a href="#uc_cta">Contact</a></li>
                            </ul>
                        </div>
                        <div class="uc-navbar-right">
                            <ul class="nav-x d-none lg:d-flex">
                                <li><a href="client/client-login.html">Login</a></li>
                            </ul>
                            <a class="btn btn-sm btn-primary text-tertiary dark:bg-tertiary dark:text-primary dark:hover:bg-tertiary fw-bold rounded-pill lg:px-2 text-none hover:contrast-shadow d-none lg:d-inline-flex"
                                href="#uc_cta">Talk to HR Seva</a>
                            <a class="d-block lg:d-none" href="#uc-menu-panel" data-uc-navbar-toggle-icon
                                data-uc-toggle></a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Header end -->

    <!-- Wrapper start -->
    <div id="wrapper" class="wrap">

        <!-- Section start -->
        <div id="hero_header" class="hero-header hero-seven-scene section panel overflow-hidden">
            <div class="position-absolute top-0 start-0 end-0 h-screen bg-tertiary-300 dark:bg-primary-700"></div>
            <div
                class="position-absolute top-0 start-0 end-0 h-screen bg-gradient-to-b from-transparent via-transparent to-white dark:to-black">
            </div>
            <div class="section-outer panel pb-6 sm:pb-8 pt-9 xl:pt-10 xl:pb-9">
                <div class="d-none lg:d-block"
                    data-anime="targets: >*; scale: [0, 1]; opacity: [0, 1]; easing: easeOutCubic; duration: 750; delay: anime.stagger(150, {start: 500});">
                    <img src="assets-02/images/vectors/marketing.svg" alt="Icon"
                        class="d-inline-block position-absolute w-72px dark:d-none" style="top: 15%; left: 10%;">
                    <img src="assets-02/images/vectors/charts-pc.svg" alt="Icon"
                        class="d-inline-block position-absolute w-72px dark:d-none" style="top: 15%; right: 10%;">
                    <img src="assets-02/images/vectors/group.svg" alt="Icon"
                        class="d-inline-block position-absolute w-64px dark:d-none"
                        style="top: 35%; right: -1%; transform: rotate(45deg);">
                    <img src="assets-02/images/vectors/idea.svg" alt="Icon"
                        class="d-inline-block position-absolute w-48px dark:d-none" style="top: 40%; left: 15%;">
                    <img src="assets-02/images/vectors/group.svg" alt="Icon"
                        class="d-inline-block position-absolute w-64px dark:d-none" style="top: 30%; left: -1%;">
                    <img src="assets-02/images/vectors/marketing-dark.svg" alt="Icon"
                        class="d-inline-block position-absolute w-72px d-none dark:d-block"
                        style="top: 15%; left: 10%;">
                    <img src="assets-02/images/vectors/charts-pc-dark.svg" alt="Icon"
                        class="d-inline-block position-absolute w-72px d-none dark:d-block"
                        style="top: 15%; right: 10%;">
                    <img src="assets-02/images/vectors/group-dark.svg" alt="Icon"
                        class="d-inline-block position-absolute w-64px d-none dark:d-block"
                        style="top: 35%; right: -1%; transform: rotate(45deg);">
                    <img src="assets-02/images/vectors/idea-dark.svg" alt="Icon"
                        class="d-inline-block position-absolute w-48px d-none dark:d-block"
                        style="top: 40%; left: 15%;">
                    <img src="assets-02/images/vectors/group-dark.svg" alt="Icon"
                        class="d-inline-block position-absolute w-64px d-none dark:d-block"
                        style="top: 30%; left: -1%;">
                </div>
                <div class="container max-w-xl">
                    <div class="section-inner panel">
                        <div class="row child-cols-12 justify-center items-center g-8">
                            <div class="lg:col-8">
                                <div class="panel vstack items-center gap-2 px-2 text-center"
                                    data-anime="targets: >*; translateY: [48, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: anime.stagger(100, {start: 200});">
                                    <span
                                        class="fs-7 fw-bold py-narrow px-2 border rounded-pill text-primary dark:text-tertiary">HR
                                        support for growing businesses</span>
                                    <h1 class="h3 sm:h2 md:h1 lg:display-6 lh-lg mb-1 xl:mb-2 mt-2">
                                        Build a smarter workplace with
                                        <span class="px-1 bg-primary text-tertiary dark:bg-tertiary dark:text-primary">HR Seva</span>
                                    </h1>
                                    <p class="fs-6 xl:fs-3 xl:px-6">HR Seva helps you manage employees, simplify payroll, and handle compliance - all in one place, without needing a large HR team.</p>
                                    <div class="vstack md:hstack justify-center gap-2 mt-3">
                                        <a href="#uc-contact-modal" data-uc-toggle
                                            class="btn btn-md xl:btn-lg btn-alt-dark border-dark px-3 lg:px-5 fw-bold contrast-shadow-sm hover:contrast-shadow">
                                            <span>Free Trial</span>
                                        </a>
                                    </div>
                                    <div class="panel mt-3 lg:mt-4 min-w-700px text-center">
                                        <div class="row child-cols-12 lg:child-cols-4 justify-center gx-0">
                                            <div>
                                                <div class="hstack justify-center gap-1">
                                                    <span class="icon mb-narrow">
                                                        <img class="w-24px"
                                                            src="assets-02/images/custom-icons/hr-value-all-in-one.svg"
                                                            alt="Manage HR icon">
                                                    </span>
                                                    <span class="fs-7 fw-medium mb-narrow text-inherit">Manage HR</span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="hstack justify-center gap-1">
                                                    <span class="icon mb-narrow">
                                                        <img class="w-24px"
                                                            src="assets-02/images/custom-icons/hr-value-salary.svg"
                                                            alt="Run payroll icon">
                                                    </span>
                                                    <span class="fs-7 fw-medium mb-narrow text-inherit">Run payroll</span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="hstack justify-center gap-1">
                                                    <span class="icon mb-narrow">
                                                        <img class="w-24px"
                                                            src="assets-02/images/custom-icons/hr-value-compliance-handle.svg"
                                                            alt="Stay compliant icon">
                                                    </span>
                                                    <span class="fs-7 fw-medium mb-narrow text-inherit">Stay compliant</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="uc-video-scene"
                                data-anime="scale: [1.2, 1]; opacity: [0, 1]; easing: easeOutCubic; duration: 750; delay: 500;">
                                <div class="panel max-w-1000px mx-auto mt-2 rounded lg:rounded-1-5 xl:rounded-2 border border-dark contrast-shadow-lg overflow-hidden"
                                    data-anime="onscroll: .hero-header; onscroll-trigger: 0.5; translateY: [-80, 0]; scale: [0.8, 1]; easing: linear;">
                                    <img src="assets/img/portal-client-dashboard.png?v=20260323" alt="HR Seva client dashboard"
                                        class="w-100 d-block">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section end -->

        <!-- Section start -->
        <div id="companies_sponsores" class="companies-sponsores section panel overflow-hidden">
            <div class="section-outer panel">
                <div class="container sm:max-w-lg xl:max-w-xl">
                    <div class="section-inner panel">
                        <div class="brands panel vstack gap-3 sm:gap-4 xl:gap-5 text-center"
                            data-anime="onview: -200; translateY: [-16, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: 350;">
                            <p class="fs-6">Trusted by startups, SMEs, and fast-growing teams across India</p>
                            <!-- <div class="panel">
                                <div class="element-brands text-gray-900 dark:text-white mask-x">
                                    <div class="swiper"
                                        data-uc-swiper="items: 2.25; center: true; center-bounds: true; autoplay: 7000; loop: true; speed: 7000; autoplay-delay: -1; allowTouchMove: false; disableOnInteraction: true;"
                                        data-uc-swiper-s="items: 4; center: false; center-bounds: false;"
                                        data-uc-swiper-m="items: 6; gap: 100;">
                                        <div class="swiper-wrapper items-center ease-linear">
                                            <div class="brand-item swiper-slide text-center">
                                                <img class="brand-item-image h-40px xl:h-48px"
                                                    src="assets-02/images/brands/brand-01.svg" alt="Proline"
                                                    data-uc-svg>
                                            </div>
                                            <div class="brand-item swiper-slide text-center">
                                                <img class="brand-item-image h-40px xl:h-48px"
                                                    src="assets-02/images/brands/brand-02.svg" alt="Iceberg"
                                                    data-uc-svg>
                                            </div>
                                            <div class="brand-item swiper-slide text-center">
                                                <img class="brand-item-image h-40px xl:h-48px"
                                                    src="assets-02/images/brands/brand-03.svg" alt="PinPoint"
                                                    data-uc-svg>
                                            </div>
                                            <div class="brand-item swiper-slide text-center">
                                                <img class="brand-item-image h-40px xl:h-48px"
                                                    src="assets-02/images/brands/brand-04.svg" alt="Clues" data-uc-svg>
                                            </div>
                                            <div class="brand-item swiper-slide text-center">
                                                <img class="brand-item-image h-40px xl:h-48px"
                                                    src="assets-02/images/brands/brand-05.svg" alt="Snowflake"
                                                    data-uc-svg>
                                            </div>
                                            <div class="brand-item swiper-slide text-center">
                                                <img class="brand-item-image h-40px xl:h-48px"
                                                    src="assets-02/images/brands/brand-06.svg" alt="Proline"
                                                    data-uc-svg>
                                            </div>
                                            <div class="brand-item swiper-slide text-center">
                                                <img class="brand-item-image h-40px xl:h-48px"
                                                    src="assets-02/images/brands/brand-07.svg" alt="Iceberg"
                                                    data-uc-svg>
                                            </div>
                                            <div class="brand-item swiper-slide text-center">
                                                <img class="brand-item-image h-40px xl:h-48px"
                                                    src="assets-02/images/brands/brand-08.svg" alt="PinPoint"
                                                    data-uc-svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section end -->

        <!-- Section start -->
        <div id="main_features" class="main-features section panel overflow-hidden">
            <div class="section-outer panel py-4 md:py-6 xl:py-10">
                <div class="container sm:max-w-lg">
                    <div class="section-inner panel">
                        <div class="panel vstack items-center gap-2 xl:gap-3 mb-4 sm:mb-6 lg:mb-8 mx-auto text-center"
                            data-anime="onview: -200; targets: >*; translateY: [48, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: anime.stagger(100, {start: 200});">
                            <span
                                class="fs-7 fw-medium py-narrow px-2 border rounded-pill text-primary dark:text-tertiary">HR
                                Services</span>
                            <h2 class="h3 lg:h2 m-0"><span class="px-1 bg-tertiary text-primary">HR Seva</span> for
                                every stage of the employee journey</h2>
                            <p class="fs-6 xl:fs-3 xl:px-8">From hiring to payroll closure, we support the processes
                                that keep your workforce running smoothly.</p>
                        </div>
                        <div class="panel vstack items-center gap-4 md:gap-6 lg:gap-8"
                            data-anime="onview: -200; targets: >*; translateY: [48, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: anime.stagger(100, {start: 200});">
                            <div class="products-lisiting flex-none w-100">
                                <div>
                                    <div class="panel">
                                        <div
                                            class="row child-cols-12 sm:child-cols-6 lg:child-cols-4 gx-2 lg:gx-3 gy-4 lg:gy-6 col-match">
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque p-2"
                                                            src="assets-02/images/template/hr-seva-service-consultancy.svg"
                                                            alt="HR Consultancy" style="object-fit: contain;">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">HR Consultancy</h4>
                                                            <p class="fs-6 opacity-60">Get expert help for your HR
                                                                setup. We guide you on policies, compliance, and team
                                                                structure.</p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque p-2"
                                                            src="assets-02/images/template/hr-seva-service-outsourced-hr.svg"
                                                            alt="Outsourced HR Services" style="object-fit: contain;">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Outsourced HR Services</h4>
                                                            <p class="fs-6 opacity-60">We handle your HR work for you.
                                                                From payroll to compliance filing, everything managed.
                                                            </p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque p-2"
                                                            src="assets-02/images/template/hr-seva-service-self-service.svg"
                                                            alt="Employee Self-Service" style="object-fit: contain;">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Employee Self-Service</h4>
                                                            <p class="fs-6 opacity-60">Employees can manage everything
                                                                themselves. Download payslips, apply leave, and check
                                                                attendance anytime.</p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque p-2"
                                                            src="assets-02/images/template/hr-seva-service-payroll.svg"
                                                            alt="Payroll Management" style="object-fit: contain;">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Payroll Management</h4>
                                                            <p class="fs-6 opacity-60">Run salaries without mistakes. We
                                                                handle calculations, deductions, PF, ESI, and payslips.
                                                            </p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque p-2"
                                                            src="assets-02/images/template/hr-seva-service-compliance.svg"
                                                            alt="Compliance Management" style="object-fit: contain;">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Compliance Management</h4>
                                                            <p class="fs-6 opacity-60">Stay compliant without tension.
                                                                We manage PF, ESIC, PT, and all filings on time.</p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque p-2"
                                                            src="assets-02/images/template/hr-seva-service-analytics.svg"
                                                            alt="Reports and Analytics" style="object-fit: contain;">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Reports &amp; Analytics</h4>
                                                            <p class="fs-6 opacity-60">See all your HR data in one
                                                                place. Track salary, attendance, and compliance with
                                                                simple reports.</p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-none">
                                    <div class="panel">
                                        <div
                                            class="row child-cols-12 sm:child-cols-6 lg:child-cols-4 gx-2 lg:gx-3 gy-4 lg:gy-6 col-match">
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/dropbox.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Employee Communication</h4>
                                                            <p class="fs-6 opacity-60">Keep your workforce informed and
                                                                connected. Includes: Email alerts, announcements,
                                                                employee portal.</p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/slack.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Reports &amp; Analytics</h4>
                                                            <p class="fs-6 opacity-60">Make better decisions with
                                                                powerful HR insights. Includes: Payroll, attendance,
                                                                compliance &amp; MIS dashboards.</p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/paypal.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">HR Consultancy</h4>
                                                            <p class="fs-6 opacity-60">Get expert HR guidance to build
                                                                compliant and scalable workforce systems. Includes:
                                                                Policy setup, compliance consulting, HR structuring.</p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/bitbucket.png"
                                                            alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Outsourced HR Services</h4>
                                                            <p class="fs-6 opacity-60">Let experts handle your HR
                                                                operations while you focus on growth. Includes: Payroll
                                                                outsourcing, compliance filing, onboarding support.</p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/stripe.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Employee Self-Service (SaaS Platform)
                                                            </h4>
                                                            <p class="fs-6 opacity-60">Empower employees with real-time
                                                                access to HR data. Includes: Dashboard, payslips, leave
                                                                tracking, mobile access.</p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/monday.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Payroll Management</h4>
                                                            <p class="fs-6 opacity-60">Run payroll with accuracy and
                                                                confidence. Includes: Payroll processing, PF/ESI/TDS
                                                                calc, payslips, bonus &amp; LOP.</p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-none">
                                    <div class="panel">
                                        <div
                                            class="row child-cols-12 sm:child-cols-6 lg:child-cols-4 gx-2 lg:gx-3 gy-4 lg:gy-6 col-match">
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/hubspot.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Compliance Management</h4>
                                                            <p class="fs-6 opacity-60">Stay compliant without stress.
                                                                Includes: PF, ESIC, PT, labour laws, compliance
                                                                calendar.</p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/asana.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Employee Management</h4>
                                                            <p class="fs-6 opacity-60">Centralize your workforce data
                                                                with complete visibility. Includes: Employee records,
                                                                documents, letters, lifecycle tracking.</p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/slack.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Attendance &amp; Leave</h4>
                                                            <p class="fs-6 opacity-60">Track work hours and manage
                                                                leaves effortlessly. Includes: Multi-punch tracking,
                                                                leave system, shifts &amp; holidays.</p>
                                                        </div>
                                                        <!-- <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a> -->
                                                    </div>
                                                    <!-- <a href="#main_features" class="position-cover"></a> -->
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/bitbucket.png"
                                                            alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Statutory &amp; Taxation</h4>
                                                            <p class="fs-6 opacity-60">Handle taxes and statutory
                                                                calculations accurately. Includes: TDS, Form 16,
                                                                gratuity, bonus compliance.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/dropbox.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">HR Documentation</h4>
                                                            <p class="fs-6 opacity-60">Generate professional HR
                                                                documents instantly. Includes: Offer, appointment,
                                                                increment &amp; legal letters.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/drive.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Employee Communication</h4>
                                                            <p class="fs-6 opacity-60">Keep your workforce informed and
                                                                connected. Includes: Email alerts, announcements,
                                                                employee portal.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-none">
                                    <div class="panel">
                                        <div
                                            class="row child-cols-12 sm:child-cols-6 lg:child-cols-4 gx-2 lg:gx-3 gy-4 lg:gy-6 col-match">
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/paypal.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Reports &amp; Analytics</h4>
                                                            <p class="fs-6 opacity-60">Make better decisions with
                                                                powerful HR insights. Includes: Payroll, attendance,
                                                                compliance &amp; MIS dashboards.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/stripe.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">HR Consultancy</h4>
                                                            <p class="fs-6 opacity-60">Get expert HR guidance to build
                                                                compliant and scalable workforce systems. Includes:
                                                                Policy setup, compliance consulting, HR structuring.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/monday.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Outsourced HR Services</h4>
                                                            <p class="fs-6 opacity-60">Let experts handle your HR
                                                                operations while you focus on growth. Includes: Payroll
                                                                outsourcing, compliance filing, onboarding support.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/saleforce.png"
                                                            alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Employee Self-Service (SaaS Platform)
                                                            </h4>
                                                            <p class="fs-6 opacity-60">Empower employees with real-time
                                                                access to HR data. Includes: Dashboard, payslips, leave
                                                                tracking, mobile access.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/mailchimp.png"
                                                            alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Payroll Management</h4>
                                                            <p class="fs-6 opacity-60">Run payroll with accuracy and
                                                                confidence. Includes: Payroll processing, PF/ESI/TDS
                                                                calc, payslips, bonus &amp; LOP.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/zapier.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Compliance Management</h4>
                                                            <p class="fs-6 opacity-60">Stay compliant without stress.
                                                                Includes: PF, ESIC, PT, labour laws, compliance
                                                                calendar.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-none">
                                    <div class="panel">
                                        <div
                                            class="row child-cols-12 sm:child-cols-6 lg:child-cols-4 gx-2 lg:gx-3 gy-4 lg:gy-6 col-match">
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/saleforce.png"
                                                            alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Employee Management</h4>
                                                            <p class="fs-6 opacity-60">Centralize your workforce data
                                                                with complete visibility. Includes: Employee records,
                                                                documents, letters, lifecycle tracking.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/drive.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Attendance &amp; Leave</h4>
                                                            <p class="fs-6 opacity-60">Track work hours and manage
                                                                leaves effortlessly. Includes: Multi-punch tracking,
                                                                leave system, shifts &amp; holidays.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/zapier.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Statutory &amp; Taxation</h4>
                                                            <p class="fs-6 opacity-60">Handle taxes and statutory
                                                                calculations accurately. Includes: TDS, Form 16,
                                                                gratuity, bonus compliance.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/bitbucket.png"
                                                            alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">HR Documentation</h4>
                                                            <p class="fs-6 opacity-60">Generate professional HR
                                                                documents instantly. Includes: Offer, appointment,
                                                                increment &amp; legal letters.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/asana.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Employee Communication</h4>
                                                            <p class="fs-6 opacity-60">Keep your workforce informed and
                                                                connected. Includes: Email alerts, announcements,
                                                                employee portal.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                            <div>
                                                <div
                                                    class="panel overflow-hidden border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-3x2 rounded-0 border-bottom border-dark border-opacity-20 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/integrations/monday.png" alt="Title">
                                                    </figure>
                                                    <div class="content vstack items-start gap-4 p-2 lg:p-4">
                                                        <div class="vstack gap-1">
                                                            <h4 class="h5 m-0">Reports &amp; Analytics</h4>
                                                            <p class="fs-6 opacity-60">Make better decisions with
                                                                powerful HR insights. Includes: Payroll, attendance,
                                                                compliance &amp; MIS dashboards.</p>
                                                        </div>
                                                        <a href="#"
                                                            class="uc-link border-bottom fw-bold dark:text-tertiary">
                                                            <span>Get started</span>
                                                            <i class="icon-narrow unicon-arrow-right fw-bold"></i>
                                                        </a>
                                                    </div>
                                                    <a href="#main_features" class="position-cover"></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- <a href="page-integrations.html"
                                class="btn btn-md xl:btn-lg btn-primary text-tertiary dark:bg-tertiary dark:text-primary dark:hover:bg-tertiary-300 px-3 lg:px-5 fw-bold">Browse
                                all integrations</a> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section end -->

        <!-- Section start -->
        <div id="key_features" class="key-features section panel overflow-hidden uc-dark lg:px-6">
            <div class="section-outer panel py-4 md:py-6 xl:py-10 bg-secondary dark:bg-primary-700 dark:text-white dark:text-opacity-60 rounded-2 lg:rounded-4"
                data-anime="onscroll: .key-features; onscroll-trigger: 1; onscoll-duration: 150%; translateY: [-80, 0]; scale: [0.85, 1]; opacity: [0.85, 1]; easing: linear;">
                <img class="d-none lg:d-inline-block w-200px position-absolute"
                    src="assets-02/images/vectors/man-trigger.svg" alt="man-trigger" style="bottom: -5%; left: 20%;"
                    data-anime="onview: -200; scale: [0.8, 1]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: 500;">
                <img class="d-none lg:d-inline-block w-32px position-absolute text-white"
                    src="assets-02/images/vectors/appostrof.svg" alt="appostrof"
                    style="bottom: 16%; right: 33%; transform: rotate(45deg);" data-uc-svg
                    data-anime="onview: -200; scale: [0.8, 1]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: 500;">
                <div class="container sm:max-w-lg">
                    <div class="section-inner panel">
                        <div class="panel vstack items-center gap-2 xl:gap-3 mb-4 sm:mb-6 lg:mb-8 sm:max-w-600px lg:max-w-700px xl:max-w-800px mx-auto text-center"
                            data-anime="onview: -200; targets: >*; translateY: [48, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: anime.stagger(100, {start: 200});">
                            <span
                                class="fs-7 fw-medium py-narrow px-2 border rounded-pill text-primary dark:text-tertiary">Why
                                HR Seva</span>
                            <h2 class="h3 lg:h2 m-0"><span class="px-1 bg-tertiary text-primary">Reliable</span> HR
                                operations support</h2>
                            <p class="fs-6 xl:fs-3 xl:px-8">We combine practical HR execution, compliance discipline,
                                and structured systems so your team can scale with confidence.</p>
                        </div>
                        <div class="features-items row child-cols-12 sm:child-cols-6 lg:child-cols-4 g-4 lg:g-6 col-match"
                            data-anime="onview: -200; targets: >*; translateY: [48, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: anime.stagger(100, {start: 400});">
                            <div class="order-1 lg:order-0">
                                <div class="features-item vstack items-center justify-center text-center gap-4">
                                    <div class="icon-box cstack w-48px h-48px dark:bg-tertiary rounded">
                                        <img class="w-24px xl:w-32px"
                                            src="assets-02/images/custom-icons/hr-value-all-in-one.svg"
                                            alt="All HR in One Place">
                                    </div>
                                    <div class="panel">
                                        <div class="vstack gap-1">
                                            <h3 class="title h6 m-0">All HR in One Place</h3>
                                            <p class="desc fs-6 opacity-60 dark:opacity-90">We handle hiring, salary,
                                                and compliance. Everything is managed in one system.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="order-0">
                                <div class="features-item vstack items-center justify-center text-center gap-4">
                                    <div class="icon-box cstack w-48px h-48px dark:bg-tertiary rounded">
                                        <img class="w-24px xl:w-32px"
                                            src="assets-02/images/custom-icons/hr-value-process.svg"
                                            alt="Easy and Clear Process">
                                    </div>
                                    <div class="panel">
                                        <div class="vstack gap-1">
                                            <h3 class="title h6 m-0">Easy &amp; Clear Process</h3>
                                            <p class="desc fs-6 opacity-60 dark:opacity-90">No confusion or manual work.
                                                Everything follows a simple system.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="order-2 sm:order-1 lg:order-0">
                                <div class="features-item vstack items-center justify-center text-center gap-4">
                                    <div class="icon-box cstack w-48px h-48px dark:bg-tertiary rounded">
                                        <img class="w-24px xl:w-32px"
                                            src="assets-02/images/custom-icons/hr-value-legal.svg"
                                            alt="Legal Work Done Right">
                                    </div>
                                    <div class="panel">
                                        <div class="vstack gap-1">
                                            <h3 class="title h6 m-0">Legal Work Done Right</h3>
                                            <p class="desc fs-6 opacity-60 dark:opacity-90">All your company filings are
                                                handled properly. No risk, no tension.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="order-1 lg:order-0">
                                <div class="features-item vstack items-center justify-center text-center gap-4">
                                    <div class="icon-box cstack w-48px h-48px dark:bg-tertiary rounded">
                                        <img class="w-24px xl:w-32px"
                                            src="assets-02/images/custom-icons/hr-value-salary.svg"
                                            alt="Correct Salary Every Time">
                                    </div>
                                    <div class="panel">
                                        <div class="vstack gap-1">
                                            <h3 class="title h6 m-0">Correct Salary Every Time</h3>
                                            <p class="desc fs-6 opacity-60 dark:opacity-90">Salaries are calculated and
                                                paid correctly. No mistakes, no delays.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="order-3 lg:order-0">
                                <div class="features-item vstack items-center justify-center text-center gap-4">
                                    <div class="icon-box cstack w-48px h-48px dark:bg-tertiary rounded">
                                        <img class="w-24px xl:w-32px"
                                            src="assets-02/images/custom-icons/hr-value-dashboard.svg"
                                            alt="See Everything Anytime">
                                    </div>
                                    <div class="panel">
                                        <div class="vstack gap-1">
                                            <h3 class="title h6 m-0">See Everything Anytime</h3>
                                            <p class="desc fs-6 opacity-60 dark:opacity-90">Check employee data and
                                                reports anytime. Everything in one dashboard.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="order-2 lg:order-0">
                                <div class="features-item vstack items-center justify-center text-center gap-4">
                                    <div class="icon-box cstack w-48px h-48px dark:bg-tertiary rounded">
                                        <img class="w-24px xl:w-32px"
                                            src="assets-02/images/custom-icons/hr-value-support.svg"
                                            alt="HR Help When You Need">
                                    </div>
                                    <div class="panel">
                                        <div class="vstack gap-1">
                                            <h3 class="title h6 m-0">HR Help When You Need</h3>
                                            <p class="desc fs-6 opacity-60 dark:opacity-90">Get support for any HR work
                                                or issue. Like having your own HR team.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="order-3 lg:order-0">
                                <div class="features-item vstack items-center justify-center text-center gap-4">
                                    <div class="icon-box cstack w-48px h-48px dark:bg-tertiary rounded">
                                        <img class="w-24px xl:w-32px"
                                            src="assets-02/images/custom-icons/hr-value-compliance-handle.svg"
                                            alt="Compliance We Handle">
                                    </div>
                                    <div class="panel">
                                        <div class="vstack gap-1">
                                            <h3 class="title h6 m-0">Compliance We Handle</h3>
                                            <p class="desc fs-6 opacity-60 dark:opacity-90">You get the portal + we do
                                                all filings. PF, ESIC, PT, everything done.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="order-3 lg:order-0">
                                <div class="features-item vstack items-center justify-center text-center gap-4">
                                    <div class="icon-box cstack w-48px h-48px dark:bg-tertiary rounded">
                                        <img class="w-24px xl:w-32px"
                                            src="assets-02/images/custom-icons/hr-value-growth.svg"
                                            alt="Grows With Your Business">
                                    </div>
                                    <div class="panel">
                                        <div class="vstack gap-1">
                                            <h3 class="title h6 m-0">Grows With Your Business</h3>
                                            <p class="desc fs-6 opacity-60 dark:opacity-90">Works for small teams and
                                                big companies. No need to change systems later.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="order-3 lg:order-0">
                                <div class="features-item vstack items-center justify-center text-center gap-4">
                                    <div class="icon-box cstack w-48px h-48px dark:bg-tertiary rounded">
                                        <img class="w-24px xl:w-32px"
                                            src="assets-02/images/custom-icons/hr-value-employee-access.svg"
                                            alt="Easy Employee Access">
                                    </div>
                                    <div class="panel">
                                        <div class="vstack gap-1">
                                            <h3 class="title h6 m-0">Easy Employee Access</h3>
                                            <p class="desc fs-6 opacity-60 dark:opacity-90">Employees can check salary,
                                                leave, and attendance. Anytime, from any device.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="pre-cta vstack items-center gap-1 max-w-400px lg:max-w-750px mx-auto text-center mt-6 xl:mt-10"
                            data-anime="onview:-100; targets: >*; translateY: [48, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: anime.stagger(100, {start: 200});">
                            <h2 class="h4 xl:h3 m-0">Simplify your HR processes and spend more time building your
                                business today</h2>
                            <p class="fs-6 sm:fs-5 text-dark dark:text-white text-opacity-70">Let HR Seva handle your
                                regular HR tasks, so you can focus on your business and team.</p>
                            <div class="vstack md:hstack justify-center gap-2 mt-3">
                                <a href="#uc-contact-modal" data-uc-toggle
                                    class="btn btn-md xl:btn-lg btn-alt-dark border-dark px-3 lg:px-5 fw-bold contrast-shadow-sm hover:contrast-shadow">
                                    <span>Book a free call</span>
                                </a>
                            </div>
                            <span class="fs-7 mt-1">Flexible support for growing teams.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section end -->

        <!-- Section start -->
        <div id="pricing_tiers" class="pricing-tiers section panel">
            <div class="section-outer panel py-4 md:py-6 xl:py-10">
                <div class="container">
                    <div class="section-inner panel">
                        <div class="panel vstack items-center gap-4 sm:gap-6 xl:gap-8">
                            <div class="heading vstack gap-2 items-center panel text-center"
                                data-anime="onview: -200; targets: >*; translateY: [48, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: anime.stagger(100, {start: 200});">
                                <span class="fs-7 fw-medium py-narrow px-2 border rounded-pill text-primary dark:text-tertiary">HR Seva Plans</span>
                                <h2 class="title h3 lg:h2 xl:h1 m-0">Choose the right <span class="px-1 bg-tertiary text-primary">HR support plan</span></h2>
                                <p class="fs-6 xl:fs-5">Start with a free trial, then scale into payroll and compliance support as your team grows.</p>
                            </div>
                            
                            <div class="content panel">
                                <div class="row child-cols-12 sm:child-cols-6 xl:child-cols-4 justify-center g-2 lg:g-4 items-start" data-anime="onview: -100; targets: >*; translateY: [48, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: anime.stagger(100, {start: 400});">
                                    <div>
                                        <div class="tier panel vstack justify-between rounded-1-5 lg:rounded-2 bg-white border border-primary border-opacity-10 text-dark text-center shadow-xs">
                                            <header class="tier-header vstack gap-2 items-center p-3 md:p-4 pb-0 md:pb-0 pt-4 md:pt-6">
                                                <span class="icon-box cstack w-48px h-48px rounded-circle bg-tertiary text-primary shadow-xs">
                                                    <i class="icon-1 unicon-sub-volume fw-bold"></i>
                                                </span>
                                                <h5 class="h5 lg:h4 m-0 text-primary">Free Trial</h5>
                                                <div class="d-flex gap-narrow items-end justify-center mt-1 flex-nowrap">
                                                    <h3 class="h2 lg:display-6 price m-0 text-dark">&#8377;0</h3>
                                                    <span class="h6 lg:h4 m-0 pb-narrow text-dark">for 30 days</span>
                                                </div>
                                                <p class="desc">Try the full HR system before you pay.</p>
                                            </header>
                                            <div class="tier-body p-3 md:p-4">
                                                <ul class="nav-y gap-2 text-start">
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">HR System (Portal)</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Employee Management (Employee Master)</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Employee Login Portal (Self-Service Access)</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Unlimited Role-Based Access</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Shift / Roster Management</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Attendance Sheet</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Leave Management</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Salary Sheet</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Payslip Generator</span>
                                                    </li>
                                                    <li class="mt-1">
                                                        <details class="plan-details d-flex flex-column">
                                                            <summary class="fw-medium text-primary order-2 mt-2" data-more-label="View more" data-less-label="View less">View more</summary>
                                                            <ul class="nav-y gap-2 text-start mt-2 order-1">
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Advance Salary</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Bonus &amp; Gratuity Management</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Full &amp; Final (FNF)</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">HR Workflows &amp; Automation</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Monthly HR Support</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Employee Helpdesk Support</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Compliance Guidance</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Company Profile Management</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">On-Call HR Assistance</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Download Reports (HR, Payroll, Attendance)</span></li>
                                                            </ul>
                                                        </details>
                                                    </li>
                                                </ul>
                                            </div>
                                            <footer class="tier-footer p-3 md:p-4 border-top">
                                                <a class="btn btn-md btn-primary text-white rounded-default w-100" href="#uc-contact-modal" data-uc-toggle>
                                                    <span>Start Free Trial</span>
                                                </a>
                                            </footer>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="tier panel vstack justify-between rounded-1-5 lg:rounded-2 bg-white border border-primary border-opacity-10 text-dark text-center shadow-xs">
                                            <header class="tier-header vstack gap-2 items-center p-3 md:p-4 pb-0 md:pb-0 pt-4 md:pt-6">
                                                <span class="icon-box cstack w-48px h-48px rounded-circle bg-tertiary text-primary shadow-xs">
                                                    <i class="icon-1 unicon-course fw-bold"></i>
                                                </span>
                                                <h5 class="h5 lg:h4 m-0 text-primary">Starter</h5>
                                                <div class="d-flex gap-narrow items-end justify-center mt-1 flex-nowrap">
                                                    <h3 class="h2 lg:display-6 price m-0 text-dark">&#8377;1,999</h3>
                                                    <span class="h6 lg:h4 m-0 pb-narrow text-dark">/ month</span>
                                                </div>
                                                <p class="desc">Complete HR &amp; payroll management.</p>
                                            </header>
                                            <div class="tier-body p-3 md:p-4">
                                                <ul class="nav-y gap-2 text-start">
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">HR System (Portal)</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Employee Management (Employee Master)</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Employee Login Portal (Self-Service Access)</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Unlimited Role-Based Access</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Shift / Roster Management</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Attendance Sheet</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Leave Management</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Salary Sheet</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Payslip Generator</span>
                                                    </li>
                                                    <li class="mt-1">
                                                        <details class="plan-details d-flex flex-column">
                                                            <summary class="fw-medium text-primary order-2 mt-2" data-more-label="View more" data-less-label="View less">View more</summary>
                                                            <ul class="nav-y gap-2 text-start mt-2 order-1">
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Advance Salary</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Bonus &amp; Gratuity Management</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Full &amp; Final (FNF)</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">HR Workflows &amp; Automation</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Monthly HR Support</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Employee Helpdesk Support</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Compliance Guidance</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Company Profile Management</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">On-Call HR Assistance</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Download Reports (HR, Payroll, Attendance)</span></li>
                                                            </ul>
                                                        </details>
                                                    </li>
                                                </ul>
                                            </div>
                                            <footer class="tier-footer p-3 md:p-4 border-top">
                                                <a class="btn btn-md btn-primary text-white rounded-default w-100" href="#uc-contact-modal" data-uc-toggle>
                                                    <span>Choose Starter</span>
                                                </a>
                                            </footer>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="tier panel vstack justify-between rounded-1-5 lg:rounded-2 bg-tertiary border border-2 border-primary text-dark text-center shadow-sm">
                                            <span class="fs-7 position-absolute top-0 end-0 fw-bold text-uppercase bg-primary text-tertiary rounded-pill px-1 py-narrow my-2 mx-3">Most popular</span>
                                            <header class="tier-header vstack gap-2 items-center p-3 md:p-4 pb-0 md:pb-0 pt-4 md:pt-6">
                                                <span class="icon-box cstack w-48px h-48px rounded-circle bg-white text-primary shadow-xs">
                                                    <i class="icon-1 unicon-building fw-bold"></i>
                                                </span>
                                                <h5 class="h5 lg:h4 m-0 text-primary">Premium</h5>
                                                <div class="d-flex gap-narrow items-end justify-center mt-1 flex-nowrap">
                                                    <h3 class="h2 lg:display-6 price m-0 text-dark">&#8377;2,999</h3>
                                                    <span class="h6 lg:h4 m-0 pb-narrow text-dark">/ month</span>
                                                </div>
                                                <p class="desc">HR system + compliance filing support.</p>
                                            </header>
                                            <div class="tier-body p-3 md:p-4">
                                                <ul class="nav-y gap-2 text-start">
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">HR System (Portal)</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Employee Management (Employee Master)</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Employee Login Portal (Self-Service Access)</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Unlimited Role-Based Access</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Shift / Roster Management</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Attendance Sheet</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Leave Management</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Salary Sheet</span>
                                                    </li>
                                                    <li class="hstack items-start gap-1">
                                                        <i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i>
                                                        <span class="d-inline">Payslip Generator</span>
                                                    </li>
                                                    <li class="mt-1">
                                                        <details class="plan-details d-flex flex-column">
                                                            <summary class="fw-medium text-primary order-2 mt-2" data-more-label="View more" data-less-label="View less">View more</summary>
                                                            <ul class="nav-y gap-2 text-start mt-2 order-1">
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Advance Salary</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Bonus &amp; Gratuity Management</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Full &amp; Final (FNF)</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">HR Workflows &amp; Automation</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Monthly HR Support</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Employee Helpdesk Support</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Compliance Guidance</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Company Profile Management</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">On-Call HR Assistance</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Download Reports (HR, Payroll, Attendance)</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline"><b>Included Filing Support</b></span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">ESIC Filing</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">PF ECR Filing</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Professional Tax (PT) Filing</span></li>
                                                                <li class="hstack items-start gap-1"><i class="cstack w-24px h-24px bg-primary-100 text-primary rounded-circle unicon-checkmark fw-bold"></i><span class="d-inline">Labour Law Returns Filing</span></li>
                                                            </ul>
                                                        </details>
                                                    </li>
                                                </ul>
                                            </div>
                                            <footer class="tier-footer p-3 md:p-4 border-top">
                                                <a class="btn btn-md btn-primary text-white rounded-default w-100" href="#uc-contact-modal" data-uc-toggle>
                                                    <span>Choose Premium</span>
                                                </a>
                                            </footer>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
          <!-- <div id="clients_feedbacks" class="clients-feedbacks section panel overflow-hidden">
            <div class="section-outer panel pb-6 xl:pb-9">
                <div class="container">
                    <div class="section-inner panel">
                        <div class="panel vstack justify-center items-center gap-4 sm:gap-6 xl:gap-8"
                            data-anime="onview: -200; targets: > *; translateY: [48, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 450; delay: anime.stagger(100, {start: 200});">
                            <h2 class="h4 sm:h3 lg:h2 m-0 text-center max-w-650px mx-auto">See why growing teams rely on
                                <span class="px-1 bg-tertiary text-primary">HR Seva</span>
                            </h2>
                            <div class="panel w-100">
                                <div class="brands panel vstack gap-3 sm:gap-4 xl:gap-5 mb-4 lg:mb-6 xl:mb-8 text-center"
                                    data-anime="onview: -200; translateY: [-16, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: 350;">
                                    <p class="h6">Trusted by founders, operations heads, and HR managers</p>
                                    <div class="panel">
                                        <div class="element-brands text-gray-900 dark:text-white mask-x">
                                            <div class="swiper"
                                                data-uc-swiper="items: 2.25; center: true; center-bounds: true; autoplay: 7000; loop: true; speed: 7000; autoplay-delay: -1; allowTouchMove: false; disableOnInteraction: true;"
                                                data-uc-swiper-s="items: 4; center: false; center-bounds: false;"
                                                data-uc-swiper-m="items: 6; gap: 100;">
                                                <div class="swiper-wrapper items-center ease-linear">
                                                    <div class="brand-item swiper-slide text-center">
                                                        <img class="brand-item-image h-40px xl:h-48px"
                                                            src="assets-02/images/brands/brand-01.svg" alt="Proline"
                                                            data-uc-svg>
                                                    </div>
                                                    <div class="brand-item swiper-slide text-center">
                                                        <img class="brand-item-image h-40px xl:h-48px"
                                                            src="assets-02/images/brands/brand-02.svg" alt="Iceberg"
                                                            data-uc-svg>
                                                    </div>
                                                    <div class="brand-item swiper-slide text-center">
                                                        <img class="brand-item-image h-40px xl:h-48px"
                                                            src="assets-02/images/brands/brand-03.svg" alt="PinPoint"
                                                            data-uc-svg>
                                                    </div>
                                                    <div class="brand-item swiper-slide text-center">
                                                        <img class="brand-item-image h-40px xl:h-48px"
                                                            src="assets-02/images/brands/brand-04.svg" alt="Clues"
                                                            data-uc-svg>
                                                    </div>
                                                    <div class="brand-item swiper-slide text-center">
                                                        <img class="brand-item-image h-40px xl:h-48px"
                                                            src="assets-02/images/brands/brand-05.svg" alt="Snowflake"
                                                            data-uc-svg>
                                                    </div>
                                                    <div class="brand-item swiper-slide text-center">
                                                        <img class="brand-item-image h-40px xl:h-48px"
                                                            src="assets-02/images/brands/brand-06.svg" alt="Proline"
                                                            data-uc-svg>
                                                    </div>
                                                    <div class="brand-item swiper-slide text-center">
                                                        <img class="brand-item-image h-40px xl:h-48px"
                                                            src="assets-02/images/brands/brand-07.svg" alt="Iceberg"
                                                            data-uc-svg>
                                                    </div>
                                                    <div class="brand-item swiper-slide text-center">
                                                        <img class="brand-item-image h-40px xl:h-48px"
                                                            src="assets-02/images/brands/brand-08.svg" alt="PinPoint"
                                                            data-uc-svg>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="swiper overflow-unset"
                                    data-uc-swiper="items: 1.25; active: 1; gap: 16; center: true; center-bounds: true;"
                                    data-uc-swiper-l="items: 4; gap: 24; center: false; center-bounds: false;">
                                    <div class="swiper-wrapper">
                                        <div class="swiper-slide">
                                            <div
                                                class="px-3 sm:px-4 py-4 panel vstack justify-between gap-3 rounded-2 border hover:contrast-shadow-md hover:border-dark duration-150">
                                                <div class="panel vstack items-start gap-2">
                                                    <p class="fs-6 lg:fs-5 text-dark dark:text-white text-opacity-70">
                                                        “We’re looking for people who share our vision! most of our time
                                                        used to be taken up by most of alternate administrative work
                                                        whereas now we can focus on building out to help our employees.”
                                                    </p>
                                                </div>
                                                <div class="panel hstack gap-2 mt-2">
                                                    <img class="w-40px rounded-circle"
                                                        src="assets-02/images/avatars/01.png" alt="Mark Zellers">
                                                    <div class="panel vstack justify-center gap-narrow">
                                                        <ul class="nav-x gap-0 text-warning">
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                        </ul>
                                                        <span class="fw-bold ft-secondary m-0">Mark Zellers</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="swiper-slide">
                                            <div
                                                class="px-3 sm:px-4 py-4 panel vstack justify-between gap-3 rounded-2 border hover:contrast-shadow-md hover:border-dark duration-150">
                                                <div class="panel vstack items-start gap-2">
                                                    <p class="fs-6 lg:fs-5 text-dark dark:text-white text-opacity-70">
                                                        “This powerfull tool eliminates the need to leave Salesforce to
                                                        get things done as I can create a custom proposal with dynamic
                                                        pricing tables, and get approval from my boss all within 36
                                                        minutes.”</p>
                                                </div>
                                                <div class="panel hstack gap-2 mt-2">
                                                    <img class="w-40px rounded-circle"
                                                        src="assets-02/images/avatars/04.png" alt="Natalia Larsson">
                                                    <div class="panel vstack justify-center gap-narrow">
                                                        <ul class="nav-x gap-0 text-warning">
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                        </ul>
                                                        <span class="fw-bold ft-secondary m-0">Natalia Larsson</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="swiper-slide">
                                            <div
                                                class="px-3 sm:px-4 py-4 panel vstack justify-between gap-3 rounded-2 border hover:contrast-shadow-md hover:border-dark duration-150">
                                                <div class="panel vstack items-start gap-2">
                                                    <p class="fs-6 lg:fs-5 text-dark dark:text-white text-opacity-70">
                                                        “We are based in Europe and the latest Data Protection
                                                        Regulation forces us to look for service suppliers than comply
                                                        with this regulation and as we look to create our website and
                                                        this builder just outstanding!”</p>
                                                </div>
                                                <div class="panel hstack gap-2 mt-2">
                                                    <img class="w-40px rounded-circle"
                                                        src="assets-02/images/avatars/03.png" alt="Sarah Edrissi">
                                                    <div class="panel vstack justify-center gap-narrow">
                                                        <ul class="nav-x gap-0 text-warning">
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                        </ul>
                                                        <span class="fw-bold ft-secondary m-0">Sarah Edrissi</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="swiper-slide">
                                            <div
                                                class="px-3 sm:px-4 py-4 panel vstack justify-between gap-3 rounded-2 border hover:contrast-shadow-md hover:border-dark duration-150">
                                                <div class="panel vstack items-start gap-2">
                                                    <p class="fs-6 lg:fs-5 text-dark dark:text-white text-opacity-70">
                                                        “We’re looking for people who share our vision! most of our time
                                                        used to be taken up by most of alternate administrative work
                                                        whereas now we can focus on building out to help our employees.”
                                                    </p>
                                                </div>
                                                <div class="panel hstack gap-2 mt-2">
                                                    <img class="w-40px rounded-circle"
                                                        src="assets-02/images/avatars/08.png" alt="Anna Yon">
                                                    <div class="panel vstack justify-center gap-narrow">
                                                        <ul class="nav-x gap-0 text-warning">
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                        </ul>
                                                        <span class="fw-bold ft-secondary m-0">Anna Yon</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="swiper-slide">
                                            <div
                                                class="px-3 sm:px-4 py-4 panel vstack justify-between gap-3 rounded-2 border hover:contrast-shadow-md hover:border-dark duration-150">
                                                <div class="panel vstack items-start gap-2">
                                                    <p class="fs-6 lg:fs-5 text-dark dark:text-white text-opacity-70">
                                                        “We’re looking for people who share our vision! most of our time
                                                        used to be taken up by most of alternate administrative work
                                                        whereas now we can focus on building out to help our employees.”
                                                    </p>
                                                </div>
                                                <div class="panel hstack gap-2 mt-2">
                                                    <img class="w-40px rounded-circle"
                                                        src="assets-02/images/avatars/01.png" alt="Mark Zellers">
                                                    <div class="panel vstack justify-center gap-narrow">
                                                        <ul class="nav-x gap-0 text-warning">
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                        </ul>
                                                        <span class="fw-bold ft-secondary m-0">Mark Zellers</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="swiper-slide">
                                            <div
                                                class="px-3 sm:px-4 py-4 panel vstack justify-between gap-3 rounded-2 border hover:contrast-shadow-md hover:border-dark duration-150">
                                                <div class="panel vstack items-start gap-2">
                                                    <p class="fs-6 lg:fs-5 text-dark dark:text-white text-opacity-70">
                                                        “This powerfull tool eliminates the need to leave Salesforce to
                                                        get things done as I can create a custom proposal with dynamic
                                                        pricing tables, and get approval from my boss all within 36
                                                        minutes.”</p>
                                                </div>
                                                <div class="panel hstack gap-2 mt-2">
                                                    <img class="w-40px rounded-circle"
                                                        src="assets-02/images/avatars/04.png" alt="Natalia Larsson">
                                                    <div class="panel vstack justify-center gap-narrow">
                                                        <ul class="nav-x gap-0 text-warning">
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                        </ul>
                                                        <span class="fw-bold ft-secondary m-0">Natalia Larsson</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="swiper-slide">
                                            <div
                                                class="px-3 sm:px-4 py-4 panel vstack justify-between gap-3 rounded-2 border hover:contrast-shadow-md hover:border-dark duration-150">
                                                <div class="panel vstack items-start gap-2">
                                                    <p class="fs-6 lg:fs-5 text-dark dark:text-white text-opacity-70">
                                                        “We are based in Europe and the latest Data Protection
                                                        Regulation forces us to look for service suppliers than comply
                                                        with this regulation and as we look to create our website and
                                                        this builder just outstanding!”</p>
                                                </div>
                                                <div class="panel hstack gap-2 mt-2">
                                                    <img class="w-40px rounded-circle"
                                                        src="assets-02/images/avatars/03.png" alt="Sarah Edrissi">
                                                    <div class="panel vstack justify-center gap-narrow">
                                                        <ul class="nav-x gap-0 text-warning">
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                        </ul>
                                                        <span class="fw-bold ft-secondary m-0">Sarah Edrissi</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="swiper-slide">
                                            <div
                                                class="px-3 sm:px-4 py-4 panel vstack justify-between gap-3 rounded-2 border hover:contrast-shadow-md hover:border-dark duration-150">
                                                <div class="panel vstack items-start gap-2">
                                                    <p class="fs-6 lg:fs-5 text-dark dark:text-white text-opacity-70">
                                                        “We’re looking for people who share our vision! most of our time
                                                        used to be taken up by most of alternate administrative work
                                                        whereas now we can focus on building out to help our employees.”
                                                    </p>
                                                </div>
                                                <div class="panel hstack gap-2 mt-2">
                                                    <img class="w-40px rounded-circle"
                                                        src="assets-02/images/avatars/08.png" alt="Anna Yon">
                                                    <div class="panel vstack justify-center gap-narrow">
                                                        <ul class="nav-x gap-0 text-warning">
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                            <li><i class="icon icon-narrow unicon-star-filled"></i></li>
                                                        </ul>
                                                        <span class="fw-bold ft-secondary m-0">Anna Yon</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <a href="#"
                                class="uc-link dark:text-tertiary fw-bold d-inline-flex items-center gap-narrow">
                                <span>Read more client stories</span>
                                <i class="icon icon-1 unicon-arrow-right rtl:rotate-180"></i>
                            </a>
                            <img class="w-500px text-primary dark:text-gray-200"
                                src="assets-02/images/vectors/producthunt-badges.svg" alt="producthunt-badges"
                                data-uc-svg="">
                        </div>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- Section end -->

        <!-- Section start -->
        <div id="faq" class="faq section panel">
            <div class="section-outer panel">
                <div class="container lg:max-w-lg">
                    <div class="section-inner panel"
                        data-anime="onview: -200; targets: >*; translateY: [48, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: anime.stagger(100, {start: 200});">
                        <div class="row child-cols-12 col-match g-4">
                            <div>
                                <div class="vstack items-center text-center gap-2">
                                    <h2 class="h4 sm:h3 xl:h2 m-0">Frequently asked questions</h2>
                                </div>
                            </div>
                            <div>
                                <div class="panel rounded-2 p-3 lg:p-8 border">
                                    <ul class="uc-accordion-divider gap-7 max-w-md mx-auto"
                                        data-uc-accordion="targets: > li; multiple: true;" style="--divider-gap: 56px">
                                        <li class="panel uc-open">
                                            <a class="uc-accordion-title h6 md:h5" href="#">What is HR Seva?</a>
                                            <div class="uc-accordion-content lg:fs-4 opacity-70">
                                                <p>HR Seva is an all-in-one platform that helps you manage employees, payroll, attendance, and compliance - with optional support to handle compliance filings for your company.</p>
                                            </div>
                                        </li>
                                        <li class="panel">
                                            <a class="uc-accordion-title h6 md:h5" href="#">Do I need HR knowledge to use HR Seva?</a>
                                            <div class="uc-accordion-content lg:fs-4 opacity-70">
                                                <p>No. HR Seva is designed for business owners and teams with little or no HR experience. Everything is simple, structured, and guided.</p>
                                            </div>
                                        </li>
                                        <li class="panel">
                                            <a class="uc-accordion-title h6 md:h5" href="#">Does HR Seva handle compliance filing?</a>
                                            <div class="uc-accordion-content lg:fs-4 opacity-70">
                                                <p>Yes. In the Premium plan, we handle compliance filings like PF, ESIC, and Professional Tax for you.</p>
                                            </div>
                                        </li>
                                        <li class="panel">
                                            <a class="uc-accordion-title h6 md:h5" href="#">What is included in the free trial?</a>
                                            <div class="uc-accordion-content lg:fs-4 opacity-70">
                                                <p>You get full access to the HR system including employee management, payroll, attendance, and reports for 30 days.</p>
                                            </div>
                                        </li>
                                        <li class="panel">
                                            <a class="uc-accordion-title h6 md:h5" href="#">Can employees access their own data?</a>
                                            <div class="uc-accordion-content lg:fs-4 opacity-70">
                                                <p>Yes. Each employee gets a secure login to view payslips, attendance, and apply for leave.</p>
                                            </div>
                                        </li>
                                        <li class="panel">
                                            <a class="uc-accordion-title h6 md:h5" href="#">What is the difference between Starter and Premium plan?</a>
                                            <div class="uc-accordion-content lg:fs-4 opacity-70">
                                                <p>Starter plan gives you the HR system to manage everything. Premium plan includes everything in Starter plus we handle your compliance filings.</p>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <!-- <div>
                                <div
                                    class="panel vstack items-center justify-between gap-2 text-center rounded-2 p-3 lg:py-8 bg-primary text-white uc-dark">
                                    <div class="panel">
                                        <div class="vstack items-center gap-2">
                                            <h2 class="h6 lg:h5 m-0">Still have questions?</h2>
                                            <p class="lg:fs-5 text-dark dark:text-white text-opacity-70">Need help
                                                choosing the right HR support plan? <br> Our team is ready to help.</p>
                                            <div class="hstack justify-center gap-0">
                                                <img src="assets-02/images/avatars/02.jpg" alt="Avatar-image"
                                                    class="w-48px h-48px rounded-circle border border-2 border-white dark:border-primary">
                                                <img src="assets-02/images/avatars/01.jpg" alt="Avatar-image"
                                                    class="w-48px h-48px rounded-circle border border-2 border-white dark:border-primary ms-n2">
                                            </div>
                                        </div>
                                    </div>
                                    <a href="#uc-contact-modal"
                                        class="btn btn-md btn-primary text-tertiary dark:bg-tertiary dark:text-primary fw-bold rounded-pill px-3 lg:px-5 mt-1 lg:mt-2">Contact
                                        HR Seva</a>
                                </div>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section end -->

        <!-- Section start -->
        <!-- <div id="blog_posts" class="blog-posts section panel overflow-hidden swiper-parent">
            <div class="section-outer panel py-4 md:py-6 xl:py-10">
                <div class="container">
                    <div class="section-inner panel">
                        <div class="row child-cols-12 g-4 sm:g-6 xl:g-8"
                            data-anime="onview: -200; targets: >*; translateY: [48, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: anime.stagger(100, {start: 200});">
                            <div>
                                <div class="heading vstack gap-2 justify-center items-center text-center panel"
                                    data-anime="onview: -200; targets: >*; translateY: [48, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 500; delay: anime.stagger(100, {start: 200});">
                                    <h2 class="title h3 xl:h2 m-0">Latest from <span
                                            class="px-1 bg-tertiary text-primary d-block lg:d-inline-block">HR
                                            insights</span></h2>
                                </div>
                            </div>
                            <div>
                                <div class="content panel">
                                    <div class="swiper p-2 swiper-match"
                                        data-uc-swiper="items: 1.1; gap: 16; loop: true; next: .swiper-nav-next; prev: .swiper-nav-prev;"
                                        data-uc-swiper-s="items: 2.3; gap: 24;" data-uc-swiper-m="items: 3.22; gap: 24;"
                                        data-uc-swiper-l="items: 4; gap: 24;">
                                        <div class="swiper-wrapper swiper-match">
                                            <div class="swiper-slide">
                                                <article
                                                    class="post type-post panel overflow-hidden vstack border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-16x9 rounded-0 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/blog/post-4.jpg"
                                                            alt="How to build a smoother employee onboarding process">
                                                        <a href="blog-details.html" class="position-cover"
                                                            data-caption="How to build a smoother employee onboarding process"></a>
                                                    </figure>
                                                    <div class="panel vstack gap-1 p-2">
                                                        <a class="text-none" href="blog-details.html">
                                                            <h3 class="post-title h5 m-0 ltr:pe-4 rtl:ps-4"><span>How to
                                                                    build a smoother employee onboarding process</span>
                                                            </h3>
                                                        </a>
                                                        <div class="content vstack justify-between gap-narrow">
                                                            <p class="post-excrept fs-6 opacity-70">A practical
                                                                onboarding checklist can reduce delays, improve
                                                                first-week experience, and help new hires become
                                                                productive sooner.</p>
                                                            <a href="blog-details.html"
                                                                class="uc-link dark:text-tertiary fs-7 xl:fs-6 fw-bold hstack gap-1 sm:mt-1 xl:mt-2">
                                                                <span>Read post</span>
                                                                <i
                                                                    class="position-relative icon unicon-arrow-up-right fw-bold rtl:-rotate-90 translate-y-px"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>
                                            <div class="swiper-slide">
                                                <article
                                                    class="post type-post panel overflow-hidden vstack border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-16x9 rounded-0 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/blog/post-5.jpg"
                                                            alt="Common payroll mistakes growing businesses should avoid">
                                                        <a href="blog-details-2.html" class="position-cover"
                                                            data-caption="Common payroll mistakes growing businesses should avoid"></a>
                                                    </figure>
                                                    <div class="panel vstack gap-1 p-2">
                                                        <a class="text-none" href="blog-details-2.html">
                                                            <h3 class="post-title h5 m-0 ltr:pe-4 rtl:ps-4"><span>Common
                                                                    payroll mistakes growing businesses should
                                                                    avoid</span></h3>
                                                        </a>
                                                        <div class="content vstack justify-between gap-narrow">
                                                            <p class="post-excrept fs-6 opacity-70">Linear helps
                                                                streamline software projects, sprints, tasks, and bug
                                                                tracking. Here’s how to get started.</p>
                                                            <a href="blog-details-2.html"
                                                                class="uc-link dark:text-tertiary fs-7 xl:fs-6 fw-bold hstack gap-1 sm:mt-1 xl:mt-2">
                                                                <span>Read post</span>
                                                                <i
                                                                    class="position-relative icon unicon-arrow-up-right fw-bold rtl:-rotate-90 translate-y-px"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>
                                            <div class="swiper-slide">
                                                <article
                                                    class="post type-post panel overflow-hidden vstack border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-16x9 rounded-0 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/blog/post-6.jpg"
                                                            alt="Why compliance documentation matters for scaling teams">
                                                        <a href="blog-details-3.html" class="position-cover"
                                                            data-caption="Why compliance documentation matters for scaling teams"></a>
                                                    </figure>
                                                    <div class="panel vstack gap-1 p-2">
                                                        <a class="text-none" href="blog-details-3.html">
                                                            <h3 class="post-title h5 m-0 ltr:pe-4 rtl:ps-4"><span>Why
                                                                    compliance documentation matters for scaling
                                                                    teams</span></h3>
                                                        </a>
                                                        <div class="content vstack justify-between gap-narrow">
                                                            <p class="post-excrept fs-6 opacity-70">Documentation gaps
                                                                become expensive as teams grow. Strong HR records
                                                                protect both employee experience and business
                                                                continuity.</p>
                                                            <a href="blog-details-3.html"
                                                                class="uc-link dark:text-tertiary fs-7 xl:fs-6 fw-bold hstack gap-1 sm:mt-1 xl:mt-2">
                                                                <span>Read post</span>
                                                                <i
                                                                    class="position-relative icon unicon-arrow-up-right fw-bold rtl:-rotate-90 translate-y-px"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>
                                            <div class="swiper-slide">
                                                <article
                                                    class="post type-post panel overflow-hidden vstack border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-16x9 rounded-0 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/blog/img-01.jpg"
                                                            alt="How HR support improves employee retention">
                                                        <a href="blog-details.html" class="position-cover"
                                                            data-caption="How HR support improves employee retention"></a>
                                                    </figure>
                                                    <div class="panel vstack gap-1 p-2">
                                                        <a class="text-none" href="blog-details.html">
                                                            <h3 class="post-title h5 m-0 ltr:pe-4 rtl:ps-4"><span>How HR
                                                                    support improves employee retention</span></h3>
                                                        </a>
                                                        <div class="content vstack justify-between gap-narrow">
                                                            <p class="post-excrept fs-6 opacity-70">Employees stay
                                                                longer when communication, policies, and support systems
                                                                are clear from day one.</p>
                                                            <a href="blog-details.html"
                                                                class="uc-link dark:text-tertiary fs-7 xl:fs-6 fw-bold hstack gap-1 sm:mt-1 xl:mt-2">
                                                                <span>Read post</span>
                                                                <i
                                                                    class="position-relative icon unicon-arrow-up-right fw-bold rtl:-rotate-90 translate-y-px"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>
                                            <div class="swiper-slide">
                                                <article
                                                    class="post type-post panel overflow-hidden vstack border bg-white dark:bg-gray-900 dark:text-white hover:border-dark hover:contrast-shadow duration-150 hover:-translate-y-1 rounded-1-5">
                                                    <figure
                                                        class="featured-image m-0 rounded ratio ratio-16x9 rounded-0 uc-transition-toggle overflow-hidden">
                                                        <img class="media-cover image uc-transition-scale-up uc-transition-opaque"
                                                            src="assets-02/images/blog/img-02.jpg"
                                                            alt="What to prepare before outsourcing HR operations">
                                                        <a href="blog-details-2.html" class="position-cover"
                                                            data-caption="What to prepare before outsourcing HR operations"></a>
                                                    </figure>
                                                    <div class="panel vstack gap-1 p-2">
                                                        <a class="text-none" href="blog-details-2.html">
                                                            <h3 class="post-title h5 m-0 ltr:pe-4 rtl:ps-4"><span>What
                                                                    to prepare before outsourcing HR operations</span>
                                                            </h3>
                                                        </a>
                                                        <div class="content vstack justify-between gap-narrow">
                                                            <p class="post-excrept fs-6 opacity-70">A simple handover on
                                                                headcount, policies, payroll inputs, and documents makes
                                                                external HR support much more effective.</p>
                                                            <a href="blog-details-2.html"
                                                                class="uc-link dark:text-tertiary fs-7 xl:fs-6 fw-bold hstack gap-1 sm:mt-1 xl:mt-2">
                                                                <span>Read post</span>
                                                                <i
                                                                    class="position-relative icon unicon-arrow-up-right fw-bold rtl:-rotate-90 translate-y-px"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="panel hstack justify-center items-centerborder contrast- gap-2">
                                    <div class="panel hstack items-end gap-narrow xl:gap-1 rtl:flex-row-reverse">
                                        <a href="#"
                                            class="swiper-nav swiper-nav-prev btn w-40px xl:w-48px h-40px xl:h-48px rounded-circle btn btn-md btn-alt-primary border dark:bg-black dark:text-white order-1">
                                            <span class="icon-1 xl:icon-2 unicon-arrow-left"></span>
                                        </a>
                                        <a href="#"
                                            class="swiper-nav swiper-nav-next btn w-40px xl:w-48px h-40px xl:h-48px rounded-circle btn btn-md btn-alt-primary border dark:bg-black dark:text-white order-2 rtl:order-0">
                                            <span class="icon-1 xl:icon-2 unicon-arrow-right"></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->

        <!-- Section end -->

        <!-- Section start -->
        <div id="uc_cta" class="uc-cta panel mb-n4 z-1 mt-4"
            data-anime="onview: -200; translateY: [48, 0]; opacity: [0, 1]; easing: easeOutCubic; duration: 500;">
            <div class="container">
                <div
                    class="panel vstack items-center gap-2 p-4 lg:p-6 xl:p-9 bg-gradient-45 from-primary to-primary-800 rounded-2 text-center text-white uc-dark">
                    <img class="position-absolute text-tertiary d-none lg:d-block"
                        src="assets-02/images/common/stars.svg" alt="stars" style="bottom: 25%; left: 10%" data-uc-svg>
                    <img class="position-absolute text-tertiary d-none lg:d-block"
                        src="assets-02/images/common/star.svg" alt="star" style="top: 25%; right: 10%" data-uc-svg>
                    <h2 class="h3 xl:display-6 lh-lg m-0 max-w-md mx-auto text-inherit"><span
                            class="px-1 bg-tertiary text-primary d-block lg:d-inline-block">HR Seva</span> for
                        dependable people operations</h2>
                    <p class="fs-6 sm:fs-5">Partner with us for hiring support, payroll confidence, compliance
                        readiness, and smoother employee operations.</p>
                    <div class="vstack md:hstack justify-center gap-2 mt-3">
                        <a href="#uc-contact-modal" data-uc-toggle
                            class="btn btn-md xl:btn-lg btn-tertiary text-primary px-3 lg:px-5 fw-bold hover:contrast-shadow">Book
                            an HR consultation</a>

                    </div>
                    <span class="fs-7 mt-1">Built for startups, SMEs, and growing organizations.</span>
                </div>
            </div>
        </div>

        <!-- Section end -->

    </div>

    <!-- Wrapper end -->

    <!-- Footer start -->
    <footer id="uc-footer" class="uc-footer panel overflow-hidden ft-tertiary ">
        <div
            class="footer-outer py-4 lg:py-6 xl:py-10 bg-gradient-to-t from-tertiary-200 dark:from-primary-700 to-primary-25 dark:to-gray-900 dark:text-white">
            <div class="uc-footer-content">
                <div class="container">
                    <div class="uc-footer-inner vstack gap-4 lg:gap-6 xl:gap-8">
                        <div class="uc-footer-widgets panel">
                            <div class="row child-cols-6 md:child-cols col-match g-4">
                                <div class="col-12 md:col-6">
                                    <div class="panel vstack items-start gap-3 xl:gap-4 md:max-w-1/2">
                                        <div>
                                            <a href="/" style="width: 140px">
                                                <img class="dark:d-none"
                                                    src="assets-02/images/common/logo-new-light.svg" alt="HR Seva">
                                                <img class="d-none dark:d-block"
                                                    src="assets-02/images/common/logo-new-dark.svg" alt="HR Seva">
                                            </a>
                                            <p class="lg:fs-5 mt-2 fw-medium">HR Seva supports businesses with
                                                practical, dependable HR operations that help teams grow with
                                                confidence.</p>
                                        </div>
                                        <div class="panel vstack items-start gap-1 fs-6 fw-medium">
                                            <a href="#uc_cta" class="text-none">Book a consultation</a>
                                            <a href="client/client-login.html" class="text-none">Login to HR Seva</a>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <ul class="nav-y gap-2 fw-bold">
                                        <li class="h6 fs-8 text-uppercase mb-2 opacity-60">Services</li>
                                        <li><a href="#main_features">HR Consultancy</a></li>
                                        <li><a href="#main_features">Outsourced HR Services</a></li>
                                        <li><a href="#main_features">Payroll Management</a></li>
                                        <li><a href="#main_features">Compliance Management</a></li>
                                        <li><a href="#main_features">Employee Self-Service</a></li>
                                    </ul>
                                </div>
                                <div>
                                    <ul class="nav-y gap-2 fw-bold">
                                        <li class="h6 fs-8 text-uppercase mb-2 opacity-60">Quick Links</li>
                                        <li><a href="#hero_header">Home</a></li>
                                        <li><a href="#key_features">Why HR Seva</a></li>
                                        <li><a href="#pricing_tiers">Pricing</a></li>
                                        <li><a href="#faq">FAQs</a></li>
                                        <li><a href="#uc_cta">Contact</a></li>
                                    </ul>
                                </div>
                                <div class="d-none lg:d-block">
                                    <ul class="nav-y gap-2 fw-bold">
                                        <li class="h6 fs-8 text-uppercase mb-2 opacity-60">Support</li>
                                        <li><a href="#uc-contact-modal" data-uc-toggle>Start free trial</a></li>
                                        <li><a href="client/client-login.html">Client login</a></li>
                                        <li><a href="#uc-privacy-modal" data-uc-toggle>Privacy policy</a></li>
                                        <li><a href="#uc-terms-modal" data-uc-toggle>Terms of use</a></li>
                                        <li><a href="#faq">Help center</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div
                            class="uc-footer-bottom panel vstack md:hstack gap-2 justify-between items-center text-center pt-4 lg:pt-6 border-top dark:text-white">
                            <p>HR Seva &copy; 2026, All rights reserved.</p>
                            <ul class="nav-x gap-1">
                                <li><a href="#uc-privacy-modal" data-uc-toggle>Privacy policy</a></li>
                                <li class="mx-2 lg:mx-3"><a href="#uc-terms-modal" data-uc-toggle>Terms of use</a></li>
                                <li><a href="#"
                                        class="btn btn-sm btn-primary text-tertiary dark:bg-tertiary dark:text-primary p-0 w-32px h-32px rounded-circle"><i
                                            class="icon icon-1 unicon-logo-facebook"></i></a></li>
                                <li><a href="#"
                                        class="btn btn-sm btn-primary text-tertiary dark:bg-tertiary dark:text-primary p-0 w-32px h-32px rounded-circle"><i
                                            class="icon icon-1 unicon-logo-x-filled"></i></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Footer end -->

    <!-- include jquery & bootstrap js -->
    <script defer src="assets-02/js/libs/jquery.min.js"></script>
    <script defer src="assets-02/js/libs/bootstrap.min.js"></script>

    <!-- include scripts -->
    <script defer src="assets-02/js/libs/anime.min.js"></script>
    <script defer src="assets-02/js/libs/swiper-bundle.min.js"></script>
    <script defer src="assets-02/js/libs/scrollmagic.min.js"></script>
    <script defer src="assets-02/js/libs/typed.min.js"></script>
    <script defer src="assets-02/js/libs/tilt.min.js"></script>
    <script defer src="assets-02/js/libs/split-type.min.js"></script>
    <script defer src="assets-02/js/libs/prettify.min.js"></script>
    <script defer src="assets-02/js/libs/gsap.min.js"></script>
    <script defer src="assets-02/js/libs/smooth-scroll.min.js"></script>
    <script defer src="assets-02/js/core/magic-cursor.js"></script>
    <script defer src="assets-02/js/helpers/data-attr-helper.js"></script>
    <script defer src="assets-02/js/helpers/swiper-helper.js"></script>
    <script defer src="assets-02/js/helpers/splitype-helper.js"></script>
    <script defer src="assets-02/js/helpers/anime-helper.js"></script>
    <script defer src="assets-02/js/helpers/typed-helper.js"></script>
    <script defer src="assets-02/js/helpers/tilt-helper.js"></script>
    <script defer src="assets-02/js/core/marquee.js"></script>
    <script defer src="assets-02/js/uikit-components-bs.js"></script>

    <!-- include app script -->
    <script defer src="assets-02/js/form.js"></script>
    <script defer src="assets-02/js/app.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.plan-details').forEach(function (details) {
                const summary = details.querySelector('summary');
                if (!summary) {
                    return;
                }

                const renderSummaryLabel = function () {
                    summary.textContent = details.open
                        ? (summary.dataset.lessLabel || 'View less')
                        : (summary.dataset.moreLabel || 'View more');
                };

                renderSummaryLabel();
                details.addEventListener('toggle', renderSummaryLabel);
            });

            const form = document.getElementById('freeTrialForm');
            if (!form) {
                return;
            }

            const steps = Array.from(form.querySelectorAll('[data-trial-step]'));
            const indicators = Array.from(form.querySelectorAll('[data-trial-indicator]'));
            const prevBtn = document.getElementById('freeTrialPrev');
            const submitBtn = document.getElementById('freeTrialSubmit');
            const messageBox = document.getElementById('freeTrialFormMessage');
            const successState = document.getElementById('freeTrialSuccessState');
            const trialModal = document.getElementById('uc-contact-modal');
            const API_BASES = ['/api'];
            let currentStep = 0;

            function showTrialMessage(text, ok) {
                if (!messageBox) {
                    return;
                }
                messageBox.textContent = text;
                messageBox.classList.remove('text-success', 'text-danger');
                if (text) {
                    messageBox.classList.add(ok ? 'text-success' : 'text-danger');
                }
            }

            async function apiFetch(path, options) {
                const errors = [];
                for (const base of API_BASES) {
                    try {
                        const res = await fetch(base + path, options);
                        if (res.status === 404 || res.status === 405) {
                            errors.push(base + path + ':' + res.status);
                            continue;
                        }
                        return res;
                    } catch (error) {
                        errors.push(String(error));
                    }
                }
                throw new Error(errors.join(' | ') || 'API unavailable');
            }

            function validateCurrentStep() {
                const visibleFields = Array.from(steps[currentStep].querySelectorAll('input, select, textarea'));
                const fullName = document.getElementById('trialFullName')?.value.trim() || '';
                const companyName = document.getElementById('trialCompanyName')?.value.trim() || '';
                for (const field of visibleFields) {
                    if (!field.checkValidity()) {
                        field.reportValidity();
                        return false;
                    }
                }

                if (currentStep === 0) {
                    if (!fullName) {
                        showTrialMessage('Please enter your full name.', false);
                        return false;
                    }
                }

                if (currentStep === 1 && !companyName) {
                    showTrialMessage('Please enter your company name.', false);
                    return false;
                }

                return true;
            }

            function showSuccessState() {
                form.hidden = true;
                if (successState) {
                    successState.hidden = false;
                }
            }

            function hideSuccessState() {
                form.hidden = false;
                if (successState) {
                    successState.hidden = true;
                }
            }

            function resetTrialFormUI() {
                form.reset();
                currentStep = 0;
                showTrialMessage('', true);
                hideSuccessState();
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
                renderStep();
            }

            function closeTrialModal() {
                const closeBtn = trialModal?.querySelector('.uc-modal-close-default');
                if (closeBtn) {
                    closeBtn.click();
                }
                window.setTimeout(resetTrialFormUI, 150);
            }

            function renderStep() {
                steps.forEach(function (step, index) {
                    step.hidden = index !== currentStep;
                });

                indicators.forEach(function (indicator, index) {
                    const isActive = index === currentStep;
                    indicator.classList.toggle('bg-primary', isActive);
                    indicator.classList.toggle('text-tertiary', isActive);
                    indicator.classList.toggle('border-primary', isActive);
                    indicator.classList.toggle('bg-secondary', !isActive);
                    indicator.classList.toggle('text-primary', !isActive);
                    indicator.classList.toggle('border-primary-200', !isActive);
                    indicator.classList.remove('text-white', 'bg-white', 'text-gray-700', 'border-gray-300');
                });

                prevBtn.hidden = currentStep === 0;
                submitBtn.textContent = currentStep === steps.length - 1 ? 'Submit request' : 'Next step';
            }

            prevBtn.addEventListener('click', function () {
                if (currentStep === 0) {
                    return;
                }
                showTrialMessage('', false);
                currentStep -= 1;
                renderStep();
            });

            document.getElementById('freeTrialSuccessDone')?.addEventListener('click', function () {
                window.setTimeout(resetTrialFormUI, 150);
            });

            document.querySelectorAll('[href="#uc-contact-modal"][data-uc-toggle]').forEach(function (trigger) {
                trigger.addEventListener('click', function () {
                    resetTrialFormUI();
                });
            });

            trialModal?.querySelectorAll('.uc-modal-close-default').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    window.setTimeout(resetTrialFormUI, 150);
                });
            });

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                showTrialMessage('', false);

                if (!validateCurrentStep()) {
                    return;
                }

                if (currentStep < steps.length - 1) {
                    currentStep += 1;
                    renderStep();
                    return;
                }

                const fullName = document.getElementById('trialFullName')?.value.trim() || '';
                const companyName = document.getElementById('trialCompanyName')?.value.trim() || '';
                const workEmail = document.getElementById('trialEmail')?.value.trim() || '';
                const phoneNo = document.getElementById('trialPhone')?.value.trim() || '';
                const teamSize = document.getElementById('trialTeamSize')?.value || '';
                const plan = document.getElementById('trialPlan')?.value || '';
                const stateName = document.getElementById('trialState')?.value || '';
                const address = document.getElementById('trialAddress')?.value.trim() || '';
                const location = document.getElementById('trialLocation')?.value.trim() || '';
                const pincode = document.getElementById('trialPincode')?.value.trim() || '';
                const websiteUrl = document.getElementById('trialWebsiteUrl')?.value.trim() || '';
                const message = [
                    plan ? 'Selected plan: ' + plan : '',
                    stateName ? 'State: ' + stateName : '',
                    address ? 'Full Address: ' + address : '',
                    location ? 'Location: ' + location : '',
                    pincode ? 'Pincode: ' + pincode : '',
                    websiteUrl ? 'Website URL: ' + websiteUrl : ''
                ].filter(Boolean).join('\n');

                const payload = {
                    fullName: fullName,
                    companyName: companyName,
                    workEmail: workEmail,
                    phoneNo: phoneNo,
                    teamSize: teamSize,
                    productInterest: 'Free Trial',
                    preferredDate: new Date().toISOString().slice(0, 10),
                    preferredTime: '',
                    modules: [],
                    message: message,
                    sourcePage: 'landing-free-trial'
                };

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Sending...';
                }

                try {
                    const res = await apiFetch('/public-enquiries', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        throw new Error(data?.detail || 'Failed to submit enquiry');
                    }
                    showTrialMessage('', true);
                    showSuccessState();
                } catch (error) {
                    showTrialMessage(error.message || 'Unable to submit your request right now.', false);
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        if (!form.hidden) {
                            renderStep();
                        }
                    }
                }
            });

            resetTrialFormUI();
        });

        // Schema toggle via URL
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        const getSchema = urlParams.get('schema');
        if (getSchema === 'dark') {
            setDarkMode(1);
        } else if (getSchema === 'light') {
            setDarkMode(0);
        }
    </script>