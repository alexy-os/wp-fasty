{/* Content */}
<div className="relative h-screen">
  <div className="max-w-4xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
    {/* Title */}
    <div className="text-center">
      <div className="mb-4 flex justify-center items-center">
        {/* Logo */}
        <a className="flex-none rounded-md text-xl inline-block font-semibold focus:outline-hidden focus:opacity-80" href="../templates.html" aria-label="UI8Kit">
        </a>
        {/* End Logo */}

        <div className="ms-2">

        </div>
      </div>

      <h1 className="text-3xl font-bold text-gray-800 sm:text-4xl dark:text-white">
        Welcome to UI8Kit AI
      </h1>
      <p className="mt-3 text-gray-600 dark:text-neutral-400">
        Your AI-powered copilot for the web
      </p>
    </div>
    {/* End Title */}

    <ul className="mt-16 space-y-5">
      {/* Chat Bubble */}
      <li className="flex gap-x-2 sm:gap-x-4">

        {/* Card */}
        <div className="inline-block bg-white border border-gray-200 rounded-lg p-4 space-y-3 dark:bg-neutral-900 dark:border-neutral-700">
          <h2 className="font-medium text-gray-800 dark:text-white">
            How can we help?
          </h2>
          <div className="space-y-1.5">
            <p className="mb-1.5 text-sm text-gray-800 dark:text-white">
              You can ask questions like:
            </p>
            <ul className="list-disc list-outside space-y-1.5 ps-3.5">
              <li className="text-sm text-gray-800 dark:text-white">
                What's UI8Kit UI?
              </li>

              <li className="text-sm text-gray-800 dark:text-white">
                How many Starter Pages & Examples are there?
              </li>

              <li className="text-sm text-gray-800 dark:text-white">
                Is there a PRO version?
              </li>
            </ul>
          </div>
        </div>
        {/* End Card */}
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="max-w-2xl ms-auto flex justify-end gap-x-2 sm:gap-x-4">
        <div className="grow text-end space-y-3">
          {/* Card */}
          <div className="inline-block bg-blue-600 rounded-lg p-4 shadow-2xs">
            <p className="text-sm text-white">
              what's UI8Kit ui?
            </p>
          </div>
          {/* End Card */}
        </div>

        <span className="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
          <span className="text-sm font-medium text-white">AZ</span>
        </span>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="flex gap-x-2 sm:gap-x-4">

        <div className="grow max-w-[90%] md:max-w-2xl w-full space-y-3">
          {/* Card */}
          <div className="inline-block bg-white border border-gray-200 rounded-lg p-4 space-y-3 dark:bg-neutral-900 dark:border-neutral-700">
            <p className="text-sm text-gray-800 dark:text-white">
              UI8Kit UI is an open-source set of prebuilt UI components based on the utility-first Tailwind CSS framework.
            </p>
            <div className="space-y-1.5">
              <p className="text-sm text-gray-800 dark:text-white">
                Here're some links to get started
              </p>
              <ul>
                <li>
                  <a className="text-sm text-blue-600 decoration-2 hover:underline focus:outline-hidden focus:underline font-medium dark:text-blue-500 dark:hover:text-blue-400" href="../docs/index.html">
                    Installation Guide
                  </a>
                </li>
                <li>
                  <a className="text-sm text-blue-600 decoration-2 hover:underline focus:outline-hidden focus:underline font-medium dark:text-blue-500 dark:hover:text-blue-400" href="../docs/frameworks.html">
                    Framework Guides
                  </a>
                </li>
              </ul>
            </div>
          </div>
          {/* End Card */}

          {/* Button Group */}
          <div>
            <div className="sm:flex sm:justify-between">
              <div>
                <div className="inline-flex border border-gray-200 rounded-full p-0.5 dark:border-neutral-700">
                  <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">

                  </button>
                  <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">

                  </button>
                </div>
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  Copy
                </button>
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  Share
                </button>
              </div>

              <div className="mt-1 sm:mt-0">
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  New answer
                </button>
              </div>
            </div>
          </div>
          {/* End Button Group */}
        </div>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="max-w-2xl ms-auto flex justify-end gap-x-2 sm:gap-x-4">
        <div className="grow text-end space-y-3">
          {/* Card */}
          <div className="inline-block bg-blue-600 rounded-lg p-4 shadow-2xs">
            <p className="text-sm text-white">
              what's UI8Kit ui figma?
            </p>
            <div className="mt-3">
              <button type="button" className="p-2 inline-flex justify-center items-center gap-x-1 rounded-lg bg-white/10 border border-transparent font-medium text-gray-100 hover:text-gray-600 hover:bg-white focus:outline-hidden focus:ring-2 ring-offset-blue-600 focus:ring-white focus:ring-offset-2 text-xs">

                Voice message
              </button>
            </div>
          </div>
          {/* End Card */}
        </div>

        <span className="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
          <span className="text-sm font-medium text-white">AZ</span>
        </span>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="flex gap-x-2 sm:gap-x-4">

        <div className="grow max-w-[90%] md:max-w-2xl w-full space-y-3">
          {/* Card */}
          <div className="inline-block bg-white border border-gray-200 rounded-lg p-4 space-y-3 dark:bg-neutral-900 dark:border-neutral-700">
            <p className="text-sm text-gray-800 dark:text-white">
              UI8Kit UI Figma is the largest free design system for Figma, crafted with Tailwind CSS styles and UI8Kit UI components with extra top-notch additions.
            </p>
            <div className="space-y-1.5">
              <p className="text-sm text-gray-800 dark:text-white">
                With the features like:
              </p>
              <ul className="list-disc list-outside space-y-1.5 ps-3.5">
                <li className="text-sm text-gray-800 dark:text-white">
                  12-column Grid System
                </li>

                <li className="text-sm text-gray-800 dark:text-white">
                  Easily find UI elements
                </li>

                <li className="text-sm text-gray-800 dark:text-white">
                  Variants and Properties
                </li>

                <li className="text-sm text-gray-800 dark:text-white">
                  Tailwind CSS Color styles
                </li>

                <li className="text-sm text-gray-800 dark:text-white">
                  Auto layout and constraints
                </li>
              </ul>
            </div>
          </div>
          {/* End Card */}

          {/* Button Group */}
          <div>
            <div className="sm:flex sm:justify-between">
              <div>
                <div className="inline-flex border border-gray-200 rounded-full p-0.5 dark:border-neutral-700">
                  <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">

                  </button>
                  <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">

                  </button>
                </div>
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  Copy
                </button>
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  Share
                </button>
              </div>

              <div className="mt-1 sm:mt-0">
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                  New answer
                </button>
              </div>
            </div>
          </div>
          {/* End Button Group */}
        </div>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="max-w-2xl ms-auto flex justify-end gap-x-2 sm:gap-x-4">
        <div className="grow text-end space-y-3">
          {/* Card */}
          <div className="inline-block bg-blue-600 rounded-lg p-4 shadow-2xs">
            <p className="text-sm text-white">
              create a table example with UI8Kit using avatars, badges and progress bars
            </p>
          </div>
          {/* End Card */}
        </div>

        <span className="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
          <span className="text-sm font-medium text-white">AZ</span>
        </span>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="flex gap-x-2 sm:gap-x-4">

        <div className="grow max-w-[90%] md:max-w-2xl w-full space-y-3">
          {/* Card */}
          <div className="inline-block bg-white border border-gray-200 rounded-lg p-4 space-y-3 dark:bg-neutral-900 dark:border-neutral-700">
            <p className="text-sm text-gray-800 dark:text-white">
              Hold on a sec...
            </p>
          </div>
          {/* End Card */}

          {/* Table Section */}
          <div className="bg-white border border-gray-200 rounded-xl shadow-2xs overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
            {/* Table */}
            <div className="-m-1.5 overflow-x-auto">
              <div className="p-1.5 min-w-full inline-block align-middle">
                <table className="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                  <thead className="bg-gray-50 dark:bg-neutral-800">
                    <tr>
                      <th scope="col" className="px-6 py-3 text-start">
                        <div className="flex items-center gap-x-2">
                          <span className="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                            Name
                          </span>
                        </div>
                      </th>

                      <th scope="col" className="px-6 py-3 text-start">
                        <div className="flex items-center gap-x-2">
                          <span className="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                            Status
                          </span>
                        </div>
                      </th>

                      <th scope="col" className="px-6 py-3 text-start">
                        <div className="flex items-center gap-x-2">
                          <span className="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                            Portfolio
                          </span>
                        </div>
                      </th>

                      <th scope="col" className="px-6 py-3 text-start">
                        <div className="flex items-center gap-x-2">
                          <span className="text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200">
                            Created
                          </span>
                        </div>
                      </th>
                    </tr>
                  </thead>

                  <tbody className="divide-y divide-gray-200 dark:divide-neutral-700">
                    <tr>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <div className="flex items-center gap-x-3">
                            <img className="inline-block size-9.5 rounded-full" src="#" alt="Avatar" />
                            <div className="grow">
                              <span className="block text-sm font-semibold text-gray-800 dark:text-neutral-200">Christina Bersh</span>
                              <span className="block text-sm text-gray-500 dark:text-neutral-500">christina@site.com</span>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <span className="inline-flex items-center gap-1.5 py-0.5 px-2 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Active
                          </span>
                        </div>
                      </td>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <div className="flex items-center gap-x-3">
                            <span className="text-xs text-gray-500 dark:text-neutral-500">1/5</span>
                            <div className="flex w-full h-1.5 bg-gray-200 rounded-full overflow-hidden dark:bg-neutral-700" role="progressbar" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
                              <div className="flex flex-col justify-center overflow-hidden bg-gray-800 text-xs text-white text-center whitespace-nowrap dark:bg-neutral-200" style="width: 25%"></div>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <span className="text-sm text-gray-500 dark:text-neutral-500">28 Dec, 12:12</span>
                        </div>
                      </td>
                    </tr>

                    <tr>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <div className="flex items-center gap-x-3">
                            <img className="inline-block size-9.5 rounded-full" src="#" alt="Avatar" />
                            <div className="grow">
                              <span className="block text-sm font-semibold text-gray-800 dark:text-neutral-200">David Harrison</span>
                              <span className="block text-sm text-gray-500 dark:text-neutral-500">david@site.com</span>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <span className="inline-flex items-center gap-1.5 py-0.5 px-2 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                            Warning
                          </span>
                        </div>
                      </td>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <div className="flex items-center gap-x-3">
                            <span className="text-xs text-gray-500 dark:text-neutral-500">3/5</span>
                            <div className="flex w-full h-1.5 bg-gray-200 rounded-full overflow-hidden dark:bg-neutral-700" role="progressbar" aria-valuenow="78" aria-valuemin="0" aria-valuemax="100">
                              <div className="flex flex-col justify-center overflow-hidden bg-gray-800 text-xs text-white text-center whitespace-nowrap dark:bg-neutral-200" style="width: 78%"></div>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <span className="text-sm text-gray-500 dark:text-neutral-500">20 Dec, 09:27</span>
                        </div>
                      </td>
                    </tr>

                    <tr>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <div className="flex items-center gap-x-3">
                            <span className="inline-flex items-center justify-center size-9.5 rounded-full bg-gray-300 dark:bg-neutral-700">
                              <span className="font-medium text-gray-800 dark:text-neutral-200">A</span>
                            </span>
                            <div className="grow">
                              <span className="block text-sm font-semibold text-gray-800 dark:text-neutral-200">Anne Richard</span>
                              <span className="block text-sm text-gray-500 dark:text-neutral-500">anne@site.com</span>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <span className="inline-flex items-center gap-1.5 py-0.5 px-2 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Active
                          </span>
                        </div>
                      </td>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <div className="flex items-center gap-x-3">
                            <span className="text-xs text-gray-500 dark:text-neutral-500">5/5</span>
                            <div className="flex w-full h-1.5 bg-gray-200 rounded-full overflow-hidden dark:bg-neutral-700" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                              <div className="flex flex-col justify-center overflow-hidden bg-gray-800 text-xs text-white text-center whitespace-nowrap dark:bg-neutral-200" style="width: 100%"></div>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <span className="text-sm text-gray-500 dark:text-neutral-500">18 Dec, 15:20</span>
                        </div>
                      </td>
                    </tr>

                    <tr>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <div className="flex items-center gap-x-3">
                            <img className="inline-block size-9.5 rounded-full" src="#" alt="Avatar" />
                            <div className="grow">
                              <span className="block text-sm font-semibold text-gray-800 dark:text-neutral-200">Samia Kartoon</span>
                              <span className="block text-sm text-gray-500 dark:text-neutral-500">samia@site.com</span>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <span className="inline-flex items-center gap-1.5 py-0.5 px-2 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Active
                          </span>
                        </div>
                      </td>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <div className="flex items-center gap-x-3">
                            <span className="text-xs text-gray-500 dark:text-neutral-500">0/5</span>
                            <div className="flex w-full h-1.5 bg-gray-200 rounded-full overflow-hidden dark:bg-neutral-700" role="progressbar" aria-valuenow="1" aria-valuemin="0" aria-valuemax="100">
                              <div className="flex flex-col justify-center overflow-hidden bg-gray-800 text-xs text-white text-center whitespace-nowrap dark:bg-neutral-200" style="width: 1%"></div>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="size-px whitespace-nowrap">
                        <div className="px-6 py-3">
                          <span className="text-sm text-gray-500 dark:text-neutral-500">18 Dec, 15:20</span>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            {/* End Table */}
          </div>
          {/* End Table Section */}

          {/* Button Group */}
          <div>
            <div className="sm:flex sm:justify-between">
              <div>
                <div className="inline-flex border border-gray-200 rounded-full p-0.5 dark:border-neutral-700">
                  <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">

                  </button>
                  <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">

                  </button>
                </div>
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  Copy
                </button>
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  Share
                </button>
              </div>

              <div className="mt-1 sm:mt-0">
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                  New answer
                </button>
              </div>
            </div>
          </div>
          {/* End Button Group */}
        </div>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="max-w-2xl ms-auto flex justify-end gap-x-2 sm:gap-x-4">
        <div className="grow text-end space-y-3">
          {/* Card */}
          <div className="inline-block bg-blue-600 rounded-lg p-4 shadow-2xs">
            <p className="text-sm text-white">
              show me the code
            </p>
          </div>
          {/* End Card */}
        </div>

        <span className="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
          <span className="text-sm font-medium text-white">AZ</span>
        </span>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="flex gap-x-2 sm:gap-x-4">

        <div className="grow max-w-[90%] md:max-w-2xl w-full space-y-3">
          {/* Card */}
          <div className="inline-block bg-white border border-gray-200 rounded-lg p-4 space-y-3 dark:bg-neutral-900 dark:border-neutral-700">
            <p className="text-sm text-gray-800 dark:text-white">
              Of course!
            </p>
          </div>
          {/* End Card */}

          <div className="mt-3 flex-none min-w-full bg-gray-800 font-mono text-sm p-5 rounded-lg dark:bg-neutral-800"><span className="text-red-500"><</span><span className="text-red-500">table <span className="text-gray-300">className="<span className="text-sky-400">min-w-full divide-y divide-gray-200 dark:divide-neutral-700</span>"</span><span className="text-red-500">></span><span className="block"></span>  <span className="ms-5 text-red-500"><</span><span className="text-red-500">thead <span className="text-gray-300">className="<span className="text-sky-400">bg-gray-50 dark:bg-neutral-800</span>"</span><span className="text-red-500">></span><span className="block"></span><span className="text-red-500">    <span className="ms-10 text-gray-500 dark:text-neutral-500">...</span></span></span></span></div>

          {/* Button Group */}
          <div>
            <div className="sm:flex sm:justify-between">
              <div>
                <div className="inline-flex border border-gray-200 rounded-full p-0.5 dark:border-neutral-700">
                  <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">

                  </button>
                  <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">

                  </button>
                </div>
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  Copy
                </button>
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  Share
                </button>
              </div>

              <div className="mt-1 sm:mt-0">
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                  New answer
                </button>
              </div>
            </div>
          </div>
          {/* End Button Group */}
        </div>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="max-w-2xl ms-auto flex justify-end gap-x-2 sm:gap-x-4">
        <div className="grow text-end space-y-3">
          {/* Card */}
          <div className="inline-block bg-blue-600 rounded-lg p-4 shadow-2xs">
            <p className="text-sm text-white">
              quiz me about tailwindcss
            </p>
          </div>
          {/* End Card */}
        </div>

        <span className="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
          <span className="text-sm font-medium text-white">AZ</span>
        </span>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="flex gap-x-2 sm:gap-x-4">

        <div className="grow max-w-[90%] md:max-w-2xl w-full space-y-3">
          {/* Card */}
          <div className="inline-block bg-white border border-gray-200 rounded-lg p-4 space-y-3 dark:bg-neutral-900 dark:border-neutral-700">
            <p className="text-sm text-gray-800 dark:text-white">
              Sure!
            </p>
          </div>
          {/* End Card */}

          <div>
            <button type="button" className="mb-2.5 me-1.5 py-2 px-3 inline-flex justify-center items-center gap-x-2 rounded-lg border border-blue-600 bg-white text-blue-600 align-middle hover:bg-blue-50 focus:outline-hidden focus:bg-blue-50 text-sm dark:bg-neutral-900 dark:text-blue-500 dark:border-blue-500 dark:hover:text-blue-400 dark:hover:border-blue-400 dark:focus:text-blue-400 dark:focus:border-blue-400">
              Is Tailwind CSS a free library?
            </button>
            <button type="button" className="mb-2.5 me-1.5 py-2 px-3 inline-flex justify-center items-center gap-x-2 rounded-lg border border-blue-600 bg-white text-blue-600 align-middle hover:bg-blue-50 focus:outline-hidden focus:bg-blue-50 text-sm dark:bg-neutral-900 dark:text-blue-500 dark:border-blue-500 dark:hover:text-blue-400 dark:hover:border-blue-400 dark:focus:text-blue-400 dark:focus:border-blue-400">
              What's the latest Tailwind CSS version?
            </button>
            <button type="button" className="mb-2.5 me-1.5 py-2 px-3 inline-flex justify-center items-center gap-x-2 rounded-lg border border-blue-600 bg-white text-blue-600 align-middle hover:bg-blue-50 focus:outline-hidden focus:bg-blue-50 text-sm dark:bg-neutral-900 dark:text-blue-500 dark:border-blue-500 dark:hover:text-blue-400 dark:hover:border-blue-400 dark:focus:text-blue-400 dark:focus:border-blue-400">
              Is it a utility-class based?
            </button>
          </div>

          {/* Button Group */}
          <div>
            <div className="sm:flex sm:justify-between">
              <div>
                <div className="inline-flex border border-gray-200 rounded-full p-0.5 dark:border-neutral-700">
                  <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">

                  </button>
                  <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">

                  </button>
                </div>
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  Copy
                </button>
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  Share
                </button>
              </div>

              <div className="mt-1 sm:mt-0">
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                  New answer
                </button>
              </div>
            </div>
          </div>
          {/* End Button Group */}
        </div>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="max-w-2xl ms-auto flex justify-end gap-x-2 sm:gap-x-4">
        <div className="grow text-end space-y-3">
          {/* Card */}
          <div className="inline-block bg-blue-600 rounded-lg p-4 shadow-2xs">
            <p className="text-sm text-white">
              generate 3-dimensional abstract images
            </p>
          </div>
          {/* End Card */}
        </div>

        <span className="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
          <span className="text-sm font-medium text-white">AZ</span>
        </span>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="flex gap-x-2 sm:gap-x-4">

        <div className="grow max-w-[90%] md:max-w-2xl w-full space-y-3">
          {/* Card */}
          <div className="inline-block bg-white border border-gray-200 rounded-lg p-4 space-y-3 dark:bg-neutral-900 dark:border-neutral-700">
            <p className="text-sm text-gray-800 dark:text-white">
              Here you go...
            </p>
          </div>
          {/* End Card */}

          <div className="grid grid-cols-2 gap-1 rounded-lg overflow-hidden">
            <div className="aspect-w-16 aspect-h-9">
              <img className="w-full object-cover" src="#" alt="Deep Learning" />
            </div>
            <div className="aspect-w-16 aspect-h-9">
              <img className="w-full object-cover" src="#" alt="Deep Learning" />
            </div>
            <div className="aspect-w-16 aspect-h-9">
              <img className="w-full object-cover" src="#" alt="Deep Learning" />
            </div>
            <div className="aspect-w-16 aspect-h-9">
              <img className="w-full object-cover" src="#" alt="Deep Learning" />
            </div>
          </div>

          {/* Button Group */}
          <div>
            <div className="sm:flex sm:justify-between">
              <div>
                <div className="inline-flex border border-gray-200 rounded-full p-0.5 dark:border-neutral-700">
                  <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">

                  </button>
                  <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">

                  </button>
                </div>
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  Copy
                </button>
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  Share
                </button>
              </div>

              <div className="mt-1 sm:mt-0">
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                  New answer
                </button>
              </div>
            </div>
          </div>
          {/* End Button Group */}
        </div>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="max-w-2xl ms-auto flex justify-end gap-x-2 sm:gap-x-4">
        <div className="grow text-end space-y-3">
          {/* Card */}
          <div className="inline-block bg-blue-600 rounded-lg p-4 shadow-2xs">
            <p className="text-sm text-white">
              what's tailwindcss?
            </p>
          </div>
          {/* End Card */}
        </div>

        <span className="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
          <span className="text-sm font-medium text-white">AZ</span>
        </span>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="flex gap-x-2 sm:gap-x-4">

        <div className="grow max-w-[90%] md:max-w-2xl w-full space-y-3">
          {/* Card */}
          <div className="inline-block bg-white border border-gray-200 rounded-lg p-4 space-y-3 dark:bg-neutral-900 dark:border-neutral-700">
            <p className="text-sm text-gray-800 dark:text-white">
              Tailwind CSS is an open source CSS framework. The main feature of this library is that, unlike other CSS frameworks like Bootstrap, it does not provide a series of predefined classes for elements such as buttons or tables.
            </p>
            <div className="space-y-1.5">
              <ul>
                <li>
                  <a className="text-sm text-blue-600 decoration-2 hover:underline focus:outline-hidden focus:underline font-medium dark:text-blue-500 dark:hover:text-blue-400" href="#">
                    Get started with Tailwind CSS
                  </a>
                </li>
                <li>
                  <a className="text-sm text-blue-600 decoration-2 hover:underline focus:outline-hidden focus:underline font-medium dark:text-blue-500 dark:hover:text-blue-400" href="#">
                    Tailwind CSS Installation guide
                  </a>
                </li>
              </ul>
            </div>
          </div>
          {/* End Card */}

          {/* Button Group */}
          <div>
            <div className="sm:flex sm:justify-between">
              <div>
                <div className="inline-flex border border-gray-200 rounded-full p-0.5 dark:border-neutral-700">
                  <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">

                  </button>
                  <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">

                  </button>
                </div>
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  Copy
                </button>
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">

                  Share
                </button>
              </div>

              <div className="mt-1 sm:mt-0">
                <button type="button" className="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                  New answer
                </button>
              </div>
            </div>
          </div>
          {/* End Button Group */}
        </div>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="max-w-2xl ms-auto flex justify-end gap-x-2 sm:gap-x-4">
        <div>
          <div className="text-end">
            <button type="button" className="mb-2.5 ms-1.5 mb-1.5 py-2 px-3 inline-flex justify-center items-center gap-x-2 rounded-lg border border-blue-600 bg-white text-blue-600 align-middle hover:bg-blue-50 focus:outline-hidden focus:bg-blue-50 text-sm dark:bg-neutral-900 dark:text-blue-500 dark:border-blue-500 dark:hover:text-blue-400 dark:hover:border-blue-400 dark:focus:text-blue-400 dark:focus:border-blue-400">
              What is the use of Tailwind CSS?
            </button>
            <button type="button" className="mb-2.5 ms-1.5 mb-1.5 py-2 px-3 inline-flex justify-center items-center gap-x-2 rounded-lg border border-blue-600 bg-white text-blue-600 align-middle hover:bg-blue-50 focus:outline-hidden focus:bg-blue-50 text-sm dark:bg-neutral-900 dark:text-blue-500 dark:border-blue-500 dark:hover:text-blue-400 dark:hover:border-blue-400 dark:focus:text-blue-400 dark:focus:border-blue-400">
              What is the difference between Tailwind CSS and CSS?
            </button>
          </div>
        </div>
      </li>
      {/* End Chat Bubble */}

      {/* Chat Bubble */}
      <li className="max-w-2xl ms-auto flex justify-end gap-x-2 sm:gap-x-4">
        <div className="grow text-end space-y-3">
          {/* Card */}
          <div className="inline-block bg-blue-600 rounded-lg p-4 shadow-2xs">
            <p className="text-sm text-white">
              2 files uploaded
            </p>
          </div>
          {/* End Card */}

          <ul className="flex flex-col justify-end text-start -space-y-px">
            <li className="flex items-center gap-x-2 p-3 text-sm bg-white border border-gray-200 text-gray-800 first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200">
              <div className="w-full flex justify-between truncate">
                <span className="me-3 flex-1 w-0 truncate">
                  resume_web_ui_developer.csv
                </span>
                <button type="button" className="flex items-center gap-x-2 text-gray-500 hover:text-blue-600 focus:outline-hidden focus:text-blue-600 whitespace-nowrap dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500">

                  Download
                </button>
              </div>
            </li>
            <li className="flex items-center gap-x-2 p-3 text-sm bg-white border border-gray-200 text-gray-800 first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200">
              <div className="w-full flex justify-between truncate">
                <span className="me-3 flex-1 w-0 truncate">
                  coverletter_web_ui_developer.pdf
                </span>
                <button type="button" className="flex items-center gap-x-2 text-gray-500 hover:text-blue-600 focus:outline-hidden focus:text-blue-600 whitespace-nowrap dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500">

                  Download
                </button>
              </div>
            </li>
          </ul>
        </div>

        <span className="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
          <span className="text-sm font-medium text-white">AZ</span>
        </span>
      </li>
      {/* End Chat Bubble */}
    </ul>
  </div>

  <div className="max-w-4xl mx-auto sticky bottom-0 z-10 bg-white border-t border-gray-200 pt-2 pb-4 sm:pt-4 sm:pb-6 px-4 sm:px-6 lg:px-0 dark:bg-neutral-900 dark:border-neutral-700">
    {/* Textarea */}
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-0">
      <div className="flex justify-between items-center mb-3">
        <button type="button" className="inline-flex justify-center items-center gap-x-2 rounded-lg font-medium text-gray-800 hover:text-blue-600 focus:outline-hidden focus:text-blue-600 text-xs sm:text-sm dark:text-neutral-200 dark:hover:text-blue-500 dark:focus:text-blue-500">

          New chat
        </button>

        <button type="button" className="py-1.5 px-2 inline-flex items-center gap-x-2 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
          Stop generating
        </button>
      </div>

      {/* Input */}
      <div className="relative">
        <textarea className="p-3 sm:p-4 pb-12 sm:pb-12 block w-full border-gray-200 rounded-lg sm:text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600" placeholder="Ask me anything..."></textarea>

        {/* Toolbar */}
        <div className="absolute bottom-px inset-x-px p-2 rounded-b-lg bg-white dark:bg-neutral-900">
          <div className="flex flex-wrap justify-between items-center gap-2">
            {/* Button Group */}
            <div className="flex items-center">
              {/* Mic Button */}
              <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-gray-500 hover:bg-gray-100 focus:z-10 focus:outline-hidden focus:bg-gray-100 dark:text-neutral-500 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">

              </button>
              {/* End Mic Button */}

              {/* Attach Button */}
              <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-gray-500 hover:bg-gray-100 focus:z-10 focus:outline-hidden focus:bg-gray-100 dark:text-neutral-500 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">

              </button>
              {/* End Attach Button */}
            </div>
            {/* End Button Group */}

            {/* Button Group */}
            <div className="flex items-center gap-x-1">
              {/* Mic Button */}
              <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-gray-500 hover:bg-gray-100 focus:z-10 focus:outline-hidden focus:bg-gray-100 dark:text-neutral-500 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">

              </button>
              {/* End Mic Button */}

              {/* Send Button */}
              <button type="button" className="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-white bg-blue-600 hover:bg-blue-500 focus:z-10 focus:outline-hidden focus:bg-blue-500">
              </button>
              {/* End Send Button */}
            </div>
            {/* End Button Group */}
          </div>
        </div>
        {/* End Toolbar */}
      </div>
      {/* End Input */}
    </div>
    {/* End Textarea */}
  </div>
</div>
{/* End Content */}