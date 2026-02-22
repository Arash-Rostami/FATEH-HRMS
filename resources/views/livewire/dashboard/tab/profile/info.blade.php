<form wire:submit="save" class="relative">
    <div class="space-y-8">
        <!-- Profile Picture & Personal Info -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Profile Picture Card -->
            <div class="lg:col-span-1">
                <x-ui.card class="h-full flex flex-col items-center justify-center text-center border-t-4 border-t-pink-500">
                    <div class="relative group">
                        @if($image)
                            <img src="{{ $image->temporaryUrl() }}" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg dark:border-gray-700" alt="New Profile" />
                        @elseif($profile->image)
                            <img src="{{ Storage::url($profile->image) }}" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg dark:border-gray-700" alt="Current Profile" />
                        @else
                            <div class="w-32 h-32 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 dark:bg-gray-700 dark:text-gray-500 border-4 border-white shadow-lg dark:border-gray-700">
                                <i class="fas fa-user text-4xl"></i>
                            </div>
                        @endif

                        <label for="profile-image-upload" class="absolute bottom-0 right-0 bg-blue-500 text-white p-2 rounded-full shadow-lg cursor-pointer hover:bg-blue-600 transition-colors dark:bg-blue-600 dark:hover:bg-blue-700">
                            <i class="fas fa-camera"></i>
                            <input type="file" id="profile-image-upload" wire:model="image" class="hidden" accept="image/*" />
                        </label>
                    </div>

                    <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">{{ Auth::user()->name }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ $profile->position ?? 'No Position' }}</p>

                    @if($profile->image && !$image)
                        <button type="button" wire:click="deleteImage" wire:confirm="Remove profile picture?" class="text-xs text-red-500 hover:text-red-700 underline">Remove Picture</button>
                    @endif
                    @error('image') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
                </x-ui.card>
            </div>

            <!-- Personal Information -->
            <div class="lg:col-span-3">
                <x-ui.card title="Personal Information" description="Basic personal details and identification." class="h-full border-t-4 border-t-blue-500">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <x-ui.input label="Personnel ID" name="profile.personnel_id" wire:model="profile.personnel_id" icon="fas fa-id-badge" />

                        <x-ui.select label="Gender" name="profile.gender" wire:model="profile.gender" icon="fas fa-venus-mars">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </x-ui.select>

                        <x-ui.select label="Marital Status" name="profile.marital_status" wire:model="profile.marital_status" icon="fas fa-ring">
                            <option value="">Select Status</option>
                            <option value="single">Single</option>
                            <option value="married">Married</option>
                        </x-ui.select>

                        <x-ui.input type="number" label="Number of Children" name="profile.number_of_children" wire:model="profile.number_of_children" icon="fas fa-child" />

                        <div class="grid grid-cols-3 gap-2 col-span-1 md:col-span-2 lg:col-span-1">
                             <x-ui.select label="Year" name="birthYear" wire:model="birthYear">
                                <option value="">Year</option>
                                @for($i = 1330; $i <= 1410; $i++) <option value="{{ $i }}">{{ $i }}</option> @endfor
                             </x-ui.select>
                             <x-ui.select label="Month" name="birthMonth" wire:model="birthMonth">
                                <option value="">Month</option>
                                @for($i = 1; $i <= 12; $i++) <option value="{{ $i }}">{{ $i }}</option> @endfor
                             </x-ui.select>
                             <x-ui.select label="Day" name="birthDay" wire:model="birthDay">
                                <option value="">Day</option>
                                @for($i = 1; $i <= 31; $i++) <option value="{{ $i }}">{{ $i }}</option> @endfor
                             </x-ui.select>
                        </div>

                        <x-ui.input label="National ID" name="profile.id_card_number" wire:model="profile.id_card_number" icon="fas fa-id-card" />
                        <x-ui.input label="ID Booklet No." name="profile.id_booklet_number" wire:model="profile.id_booklet_number" icon="fas fa-book" />
                    </div>
                </x-ui.card>
            </div>
        </div>

        <!-- Job Details -->
        <x-ui.card title="Job Details" description="Employment status, department, and position." class="border-t-4 border-t-purple-500">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-ui.select label="Department" name="profile.department_id" wire:model="profile.department_id" icon="fas fa-building">
                    <option value="">Select Department</option>
                    @foreach($departments as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.input label="Position" name="profile.position" wire:model="profile.position" icon="fas fa-user-tie" />

                <x-ui.select label="Employment Type" name="profile.employment_type" wire:model="profile.employment_type" icon="fas fa-briefcase">
                    <option value="">Select Type</option>
                    <option value="fulltime">Full Time</option>
                    <option value="parttime">Part Time</option>
                    <option value="contract">Contract</option>
                </x-ui.select>

                <x-ui.select label="Status" name="profile.employment_status" wire:model="profile.employment_status" icon="fas fa-user-check">
                    <option value="">Select Status</option>
                    <option value="probational">Probational</option>
                    <option value="working">Active</option>
                    <option value="terminated">Terminated</option>
                </x-ui.select>

                <x-ui.input label="Insurance No." name="profile.insurance" wire:model="profile.insurance" icon="fas fa-shield-alt" />
                <x-ui.input label="Work Experience" name="profile.work_experience" wire:model="profile.work_experience" icon="fas fa-history" />
            </div>
        </x-ui.card>

        <!-- Contact & Address -->
        <x-ui.card title="Contact & Address" description="How we can reach you." class="border-t-4 border-t-green-500">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <x-ui.input label="Cellphone" name="profile.cellphone" wire:model="profile.cellphone" icon="fas fa-mobile-alt" />
                <x-ui.input label="Landline" name="profile.landline" wire:model="profile.landline" icon="fas fa-phone" />
                <x-ui.input label="Emergency Phone" name="profile.emergency_phone" wire:model="profile.emergency_phone" icon="fas fa-ambulance" />
                <x-ui.input label="Relationship" name="profile.emergency_relationship" wire:model="profile.emergency_relationship" icon="fas fa-users" />
                <x-ui.input label="Postal Code" name="profile.zip_code" wire:model="profile.zip_code" icon="fas fa-envelope" />

                <div class="col-span-1 md:col-span-2 lg:col-span-3">
                    <x-ui.textarea label="Address" name="profile.address" wire:model="profile.address" icon="fas fa-map-marker-alt" rows="2" />
                </div>
            </div>
        </x-ui.card>

        <!-- Other Info -->
        <x-ui.card title="Additional Information" description="Education, interests, and other details." class="border-t-4 border-t-yellow-500">
             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-ui.select label="Degree" name="profile.degree" wire:model="profile.degree" icon="fas fa-graduation-cap">
                    <option value="">Select Degree</option>
                    <option value="undergraduate">Undergraduate</option>
                    <option value="graduate">Graduate</option>
                    <option value="postgraduate">Postgraduate</option>
                </x-ui.select>
                <x-ui.input label="Field of Study" name="profile.field" wire:model="profile.field" icon="fas fa-book-reader" />

                <div class="col-span-1 md:col-span-2">
                     <x-ui.textarea label="Interests" name="profile.interests" wire:model="profile.interests" icon="fas fa-heart" rows="2" />
                </div>
                <div class="col-span-1 md:col-span-2">
                     <x-ui.textarea label="Accessibility Needs" name="profile.accessibility" wire:model="profile.accessibility" icon="fas fa-wheelchair" rows="2" />
                </div>
             </div>
        </x-ui.card>

        <div class="sticky bottom-6 z-30 flex justify-end">
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md p-2 rounded-2xl shadow-2xl border border-white/20">
                <x-ui.button type="submit" loading="save" icon="fas fa-save" class="px-8 py-3 text-base shadow-blue-500/50">
                    Save Changes
                </x-ui.button>
            </div>
        </div>
    </div>
</form>
