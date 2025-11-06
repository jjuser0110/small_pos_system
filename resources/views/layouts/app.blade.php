<!DOCTYPE html>

<html
  lang="en"
  class="light-style layout-navbar-fixed layout-menu-fixed layout-compact"
  dir="ltr">
  <head>
    
    @include('layouts.head')
    @include('layouts.css')
  </head>

  <body>
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <div id="loading-screen" class="d-none">
            <div class="sk-wave sk-primary">
              <div class="sk-wave-rect"></div>
              <div class="sk-wave-rect"></div>
              <div class="sk-wave-rect"></div>
              <div class="sk-wave-rect"></div>
              <div class="sk-wave-rect"></div>
            </div>
        </div>
        <!-- Menu -->

        @include('layouts.sidebar')
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

		      @include('layouts.top_profile_bar')

          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            @include('layouts.flash-message')
			      @yield('content')
		        @include('layouts.footer')
          </div>
          <!-- Content wrapper -->
        </div>
        <div class="modal fade" id="basicModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <form enctype="multipart/form-data" method="post" action="{{ route('change_password') }}" onsubmit="showLoading()">
                @csrf
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel1">Change Password</h5>
                <button
                  type="button"
                  class="btn-close"
                  data-bs-dismiss="modal"
                  aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="row">
                  <div class="col mb-3">
                    <label class="col-form-label">Password</label>
                    <input class="form-control" type="password" name="new_password" placeholder="password" autocomplete='false' required>
                  </div>
                </div>
                <div class="row">
                  <div class="col mb-3">
                    <label class="col-form-label">Confirm Password</label>
                    <input class="form-control" type="password" name="new_password_confirmation" placeholder="confirm password" autocomplete='false' required>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                  Close
                </button>
                <button type="submit" class="btn btn-primary">Save changes</button>
              </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>
      <div class="drag-target"></div>
    </div>
    
    

    @include('layouts.script')
  </body>
</html>
