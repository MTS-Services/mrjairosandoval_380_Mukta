<x-frontend::layout>
    <x-slot name="title">Contact</x-slot>
    <x-slot name="page_slug">contact</x-slot>


    <div class="bg-black">

        <section class="bg-black text-white font-serif  min-screen lg:w-250 mx-auto mb-30">
            <div class="text-center ">
                <!-- Heading -->
                <h1
                    class="text-[#caa36b] text-2xl lg:text-4xl font-(family-name:--font-family-base) font-semibold pt-30 ">
                    BEGIN YOUR DISCRETION
                </h1>
                <p class="p-12 text-[#E5E3E3]   text-sm md:text-base font-(family-name:--font-family-secondary)">
                    The first test is knowing what questions to answer.
                </p>

                <!-- Form -->
                <form action="{{ route('f.contact.store') }}" enctype="multipart/form-data" method="POST" class="font-(family-name:--font-family-secondary)">
                    @csrf
                    <!-- Name -->
                    <div class="text-left   ">
                        <label class="text-xs text[#E5E3E3] m-2 text-3xl font-serif font-semibold ">Name (we'll forget
                            it
                            immediately)</label>
                        <input type="text" placeholder="Your ephemeral designation." name="first_name"
                            class="m-2 w-full bg-transparent border border-gray-800 rounded-lg text-sm text-[#E5E3E3] px-6 py-3 focus:outline-none focus:border-[#caa36b]" />
                    </div>

                    <!-- Who introduced -->
                    <div class="text-left  ">
                        <label class="text-xs text[#E5E3E3]  m-2   text-3xl font-serif font-semibold">Who
                            introduced you to...
                            certain tastes? (If
                            none, lie.)</label>
                        <input type="text" placeholder="The whisper, rounded-lg the sign, the omen..." name="subject"
                            class="m-2 rounded-lg w-full bg-transparent border border-gray-800 text-sm text-[#E5E3E3] px-6 py-3 focus:outline-none focus:border-[#caa36b]" />
                    </div>

                    <!-- Darkest virtue -->
                    <div class="text-left  ">
                        <label class=" text-xs text[#E5E3E3] m-2   text-3xl font-serif font-semibold">Your
                            darkest virtue?
                            <span class="italic">(e.g.
                                Patience)</span>
                        </label>
                        <input type="text" placeholder="The strength you conceal..." name="message"
                            class="m-2 w-full bg-transparent rounded-lg border border-gray-800 text-sm text-[#E5E3E3] px-6 py-3 focus:outline-none focus:border-[#caa36b]" />
                    </div>

                    <!-- Button -->
                    <button type="submit"
                        class="flex bg-[#caa36b] ml-2  text-sm tracking-widest px-6 py-3 mt-4 rounded focus:outline-none hover:bg-[#b48c5c] transition-colors duration-300 text-[#7D0A0A]">
                        BURN AFTER READING
                    </button>
                </form>
        </section>

        <section class="bg-black text-white font-serif flex items-center justify-center pt-25 pb-35">
            <div class="text-center  px-6">

                <!-- Eye Icon -->
                <div class="flex justify-center mb-6">
                    <img src="{{ asset('frontend/assetes/image/image 81.png') }}" alt="Eye" class="w-10 h-10">
                </div>

                <!-- Heading -->
                <h1
                    class="text-[#caa36b] text-4xl md:text-4xl tracking-wide font-semibold mb-8 font-(family-name:--font-family-secondary) ">
                    IF YOU'RE WORTHY, YOU'LL UNDERSTAND.
                </h1>

                <!-- Paragraphs -->
                <p class="text-[#E5E3E3] text-sm md:text-base mb-4 font-(family-name:--font-family-base)">
                    Your submission has been received and will be reviewed by those who matter.
                </p>
                <p class="text-[#E5E3E3] text-sm md:text-base mb-4 font-(family-name:--font-family-base)">
                    If your answers demonstrate the required... discretion, you will be contacted through channels you
                    already know.
                </p>
                <p class="text-[#E5E3E3] text-xs mb-10 font-(family-name:--font-family-base)">
                    If you do not hear from us, you were never meant to.
                </p>

                <!-- Button -->
                <button type="submit"
                    class=" bg-[#caa36b]   text-sm tracking-widest px-6 py-3 mt-4 rounded focus:outline-none hover:bg-[#b48c5c] transition-colors duration-300 text-[#7D0A0A]">
                    BURN AFTER READING
                </button>
            </div>
        </section>
    </div>

</x-frontend::layout>
